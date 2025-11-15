<?php

namespace Stokoe\FormsToHubspotConnector\Tests;

use Stokoe\FormsToHubspotConnector\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
