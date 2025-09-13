<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
	/**
	 * A basic test verifying the home route responds.
	 */
	public function testHomeRespondsWithRedirect()
	{
		$response = $this->get('/');

		$response->assertStatus(302);
	}
}
