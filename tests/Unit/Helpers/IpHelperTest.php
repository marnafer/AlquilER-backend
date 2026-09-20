<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\IpHelper;
use Tests\TestCase;

class IpHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR']
        );

        unset($_ENV['TRUSTED_PROXIES']);

        parent::tearDown();
    }

    public function test_it_returns_remote_addr_when_no_trusted_proxy_is_configured(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] = '';

        $this->assertSame(
            '192.168.1.10',
            IpHelper::obtener()
        );
    }

    public function test_it_returns_null_when_remote_addr_is_missing(): void
    {
        unset($_SERVER['REMOTE_ADDR']);

        $this->assertNull(
            IpHelper::obtener()
        );
    }

    public function test_it_returns_null_when_remote_addr_is_invalid(): void
    {
        $_SERVER['REMOTE_ADDR'] = 'ip-invalida';

        $this->assertNull(
            IpHelper::obtener()
        );
    }

    public function test_it_ignores_forwarded_for_when_remote_addr_is_not_trusted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertSame(
            '192.168.1.10',
            IpHelper::obtener()
        );
    }

    public function test_it_returns_forwarded_client_ip_when_remote_addr_is_trusted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertSame(
            '203.0.113.50',
            IpHelper::obtener()
        );
    }

    public function test_it_returns_first_non_trusted_ip_from_right_to_left(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] =
            '203.0.113.50, 10.0.0.2, 10.0.0.3';

        $_ENV['TRUSTED_PROXIES'] =
            '10.0.0.1,10.0.0.2,10.0.0.3';

        $this->assertSame(
            '203.0.113.50',
            IpHelper::obtener()
        );
    }

    public function test_it_ignores_invalid_ips_in_forwarded_for(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] =
            'ip-invalida, 203.0.113.50, 10.0.0.2';

        $_ENV['TRUSTED_PROXIES'] =
            '10.0.0.1,10.0.0.2';

        $this->assertSame(
            '203.0.113.50',
            IpHelper::obtener()
        );
    }

    public function test_it_falls_back_to_remote_addr_when_all_forwarded_ips_are_trusted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] =
            '10.0.0.2, 10.0.0.3';

        $_ENV['TRUSTED_PROXIES'] =
            '10.0.0.1,10.0.0.2,10.0.0.3';

        $this->assertSame(
            '10.0.0.1',
            IpHelper::obtener()
        );
    }

    public function test_it_returns_remote_addr_when_trusted_proxy_does_not_send_forwarded_for(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertSame(
            '10.0.0.1',
            IpHelper::obtener()
        );
    }

    public function test_it_supports_trusted_ipv4_cidr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.25';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/24';

        $this->assertSame(
            '203.0.113.50',
            IpHelper::obtener()
        );
    }

    public function test_it_does_not_trust_ipv4_outside_configured_cidr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.1.25';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] = '10.0.0.0/24';

        $this->assertSame(
            '10.0.1.25',
            IpHelper::obtener()
        );
    }

    public function test_it_supports_ipv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2001:db8::10';

        $this->assertSame(
            '2001:db8::10',
            IpHelper::obtener()
        );
    }

    public function test_it_supports_trusted_ipv6_cidr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2001:db8:1::10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8:ffff::50';

        $_ENV['TRUSTED_PROXIES'] = '2001:db8:1::/64';

        $this->assertSame(
            '2001:db8:ffff::50',
            IpHelper::obtener()
        );
    }

    public function test_it_accepts_spaces_between_trusted_proxies(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $_ENV['TRUSTED_PROXIES'] =
            ' 10.0.0.1 , 10.0.0.2 ';

        $this->assertSame(
            '203.0.113.50',
            IpHelper::obtener()
        );
    }
}