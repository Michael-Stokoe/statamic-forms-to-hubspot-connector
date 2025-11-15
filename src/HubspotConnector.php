<?php

declare(strict_types=1);

namespace Stokoe\FormsToHubspotConnector;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\Contracts\ConnectorInterface;
use Statamic\Forms\Submission;

class HubspotConnector implements ConnectorInterface
{
    public function handle(): string
    {
        return 'hubspot';
    }

    public function name(): string
    {
        return 'HubSpot';
    }

    public function fieldset(): array
    {
        return [
            [
                'handle' => 'access_token',
                'field' => [
                    'type' => 'text',
                    'display' => 'Access Token',
                    'instructions' => 'Your HubSpot private app access token',
                    'validate' => 'required',
                ],
            ],
            [
                'handle' => 'email_field',
                'field' => [
                    'type' => 'text',
                    'display' => 'Email Field',
                    'instructions' => 'Form field containing the email address',
                    'default' => 'email',
                ],
            ],
            [
                'handle' => 'create_contact',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Create Contact',
                    'instructions' => 'Create or update contact in HubSpot',
                    'default' => true,
                ],
            ],
            [
                'handle' => 'field_mapping',
                'field' => [
                    'type' => 'grid',
                    'display' => 'Field Mapping',
                    'instructions' => 'Map form fields to HubSpot contact properties',
                    'fields' => [
                        [
                            'handle' => 'form_field',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Form Field',
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'hubspot_property',
                            'field' => [
                                'type' => 'text',
                                'display' => 'HubSpot Property',
                                'instructions' => 'e.g. firstname, lastname, phone, company',
                                'width' => 50,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function process(Submission $submission, array $config): void
    {
        $accessToken = $config['access_token'] ?? null;
        $emailField = $config['email_field'] ?? 'email';
        $createContact = $config['create_contact'] ?? true;
        $fieldMapping = $config['field_mapping'] ?? [];

        if (!$accessToken) {
            Log::warning('HubSpot connector: Missing access token', [
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
            return;
        }

        $formData = $submission->data();
        $email = $formData[$emailField] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('HubSpot connector: Invalid or missing email', [
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
                'email_field' => $emailField,
                'email' => $email,
            ]);
            return;
        }

        if ($createContact) {
            $this->createOrUpdateContact($submission, $accessToken, $email, $fieldMapping, $formData);
        }
    }

    protected function createOrUpdateContact(
        Submission $submission,
        string $accessToken,
        string $email,
        array $fieldMapping,
        array $formData
    ): void {
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts';

        // Build properties from mapping
        $properties = ['email' => $email];
        
        foreach ($fieldMapping as $mapping) {
            $formField = $mapping['form_field'] ?? '';
            $hubspotProperty = $mapping['hubspot_property'] ?? '';

            if ($formField && $hubspotProperty && isset($formData[$formField])) {
                $properties[$hubspotProperty] = $formData[$formField];
            }
        }

        $payload = [
            'properties' => $properties,
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                $contact = $response->json();
                Log::info('HubSpot contact created/updated successfully', [
                    'email' => $email,
                    'contact_id' => $contact['id'] ?? null,
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                ]);
            } elseif ($response->status() === 409) {
                // Contact exists, try to update
                $this->updateExistingContact($submission, $accessToken, $email, $properties);
            } else {
                $error = $response->json();
                Log::error('HubSpot API error', [
                    'status' => $response->status(),
                    'error' => $error['message'] ?? 'Unknown error',
                    'errors' => $error['errors'] ?? [],
                    'full_response' => $error,
                    'email' => $email,
                    'form' => $submission->form()->handle(),
                    'submission_id' => $submission->id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('HubSpot connector exception', [
                'error' => $e->getMessage(),
                'email' => $email,
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
        }
    }

    protected function updateExistingContact(
        Submission $submission,
        string $accessToken,
        string $email,
        array $properties
    ): void {
        // First, find the contact by email
        $searchUrl = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
        
        $searchPayload = [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $searchResponse = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($searchUrl, $searchPayload);

            if ($searchResponse->successful()) {
                $searchResult = $searchResponse->json();
                $contacts = $searchResult['results'] ?? [];

                if (!empty($contacts)) {
                    $contactId = $contacts[0]['id'];
                    $updateUrl = "https://api.hubapi.com/crm/v3/objects/contacts/{$contactId}";

                    $updateResponse = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ])
                        ->patch($updateUrl, ['properties' => $properties]);

                    if ($updateResponse->successful()) {
                        Log::info('HubSpot contact updated successfully', [
                            'email' => $email,
                            'contact_id' => $contactId,
                            'form' => $submission->form()->handle(),
                            'submission_id' => $submission->id(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('HubSpot contact update failed', [
                'error' => $e->getMessage(),
                'email' => $email,
                'form' => $submission->form()->handle(),
                'submission_id' => $submission->id(),
            ]);
        }
    }
}
