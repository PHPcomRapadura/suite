<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_footer_carries_the_rapadura_network_badge(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('class="rpd-badge rpd-badge--dark"', false);
        $response->assertSee('href="https://rapadura.net"', false);
        $response->assertSeeInOrder(['Powered by', 'Rapadura Network']);
    }

    public function test_the_badge_appears_once_and_inside_the_footer(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertSame(1, substr_count($html, 'class="rpd-badge rpd-badge--'));
        $this->assertGreaterThan(strpos($html, '<footer class="footer"'), strpos($html, 'rpd-badge--dark'));
    }
}
