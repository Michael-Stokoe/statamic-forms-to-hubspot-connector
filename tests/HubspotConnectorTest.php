<?php

declare(strict_types=1);

namespace Stokoe\FormsToHubspotConnector\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToHubspotConnector\HubspotConnector;
use Statamic\Forms\Form;
use Statamic\Forms\Submission;

class HubspotConnectorTest extends TestCase
{
    public function test_it_has_correct_handle_and_name(): void
    {
        $connector = new HubspotConnector;
        
        $this->assertEquals('hubspot', $connector->handle());
        $this->assertEquals('HubSpot', $connector->name());
    }

    public function test_it_returns_fieldset(): void
    {
        $connector = new HubspotConnector;
        $fieldset = $connector->fieldset();
        
        $this->assertIsArray($fieldset);
        $this->assertNotEmpty($fieldset);
        
        $handles = array_column($fieldset, 'handle');
        $this->assertContains('access_token', $handles);
        $this->assertContains('email_field', $handles);
        $this->assertContains('create_contact', $handles);
    }

    public function test_it_handles_missing_access_token(): void
    {
        Log::shouldReceive('warning')->once();
        
        $connector = new HubspotConnector;
        $submission = $this->createMockSubmission();
        
        $connector->process($submission, []);
        
        $this->assertTrue(true);
    }

    public function test_it_handles_invalid_email(): void
    {
        Log::shouldReceive('warning')->once();
        
        $connector = new HubspotConnector;
        $submission = $this->createMockSubmission(['email' => 'invalid-email']);
        
        $config = [
            'access_token' => 'test-token',
            'email_field' => 'email',
        ];
        
        $connector->process($submission, $config);
        
        $this->assertTrue(true);
    }

    public function test_it_creates_contact_successfully(): void
    {
        Log::shouldReceive('info')->once();
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('withHeaders')->andReturnSelf();
        Http::shouldReceive('post')->andReturn(
            \Mockery::mock()->shouldReceive('successful')->andReturn(true)
                ->shouldReceive('json')->andReturn(['id' => '12345'])
                ->getMock()
        );
        
        $connector = new HubspotConnector;
        $submission = $this->createMockSubmission(['email' => 'test@example.com']);
        
        $config = [
            'access_token' => 'test-token',
            'email_field' => 'email',
            'create_contact' => true,
        ];
        
        $connector->process($submission, $config);
        
        $this->assertTrue(true);
    }

    private function createMockSubmission(array $data = []): Submission
    {
        $submission = \Mockery::mock(Submission::class);
        $form = \Mockery::mock(Form::class);
        
        $form->shouldReceive('handle')->andReturn('test_form');
        $submission->shouldReceive('form')->andReturn($form);
        $submission->shouldReceive('id')->andReturn('test_id');
        $submission->shouldReceive('data')->andReturn($data);
        
        return $submission;
    }
}
