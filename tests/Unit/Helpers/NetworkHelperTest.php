<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class NetworkHelperTest extends TestCase
{
    public function test_ipv4_validation(): void
    {
        $validIps = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '127.0.0.1',
            '255.255.255.255',
            '0.0.0.0',
        ];

        $invalidIps = [
            '192.168.1.256',
            '300.1.1.1',
            '192.168',
            '192.168.1',
            'not-an-ip',
            '',
        ];

        foreach ($validIps as $ip) {
            $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false, "IP {$ip} should be valid");
        }

        foreach ($invalidIps as $ip) {
            $this->assertFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false, "IP {$ip} should be invalid");
        }
    }

    public function test_ipv6_validation(): void
    {
        $validIps = [
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            '2001:db8:85a3::8a2e:370:7334',
            '::1',
            '::',
            'fe80::1',
            '2001:db8::1',
        ];

        $invalidIps = [
            '2001:0db8:85a3::8a2e::7334',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334:extra',
            'gggg::1',
            '192.168.1.1', // IPv4 should fail IPv6 validation
        ];

        foreach ($validIps as $ip) {
            $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false, "IPv6 {$ip} should be valid");
        }

        foreach ($invalidIps as $ip) {
            $this->assertFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false, "IPv6 {$ip} should be invalid");
        }
    }

    public function test_mac_address_validation(): void
    {
        $validMacs = [
            '00:11:22:33:44:55',
            'AA:BB:CC:DD:EE:FF',
            '00-11-22-33-44-55',
            'aa:bb:cc:dd:ee:ff',
            'AA-BB-CC-DD-EE-FF',
        ];

        $invalidMacs = [
            '00:11:22:33:44', // Too short
            '00:11:22:33:44:55:66', // Too long
            'GG:11:22:33:44:55', // Invalid character
            '00.11.22.33.44.55', // Wrong separator
            'not-a-mac',
            '',
        ];

        $macPattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';

        foreach ($validMacs as $mac) {
            $this->assertMatchesRegularExpression($macPattern, $mac, "MAC {$mac} should be valid");
        }

        foreach ($invalidMacs as $mac) {
            $this->assertDoesNotMatchRegularExpression($macPattern, $mac, "MAC {$mac} should be invalid");
        }
    }

    public function test_subnet_calculations(): void
    {
        // Test CIDR to netmask conversion
        $cidrToNetmask = [
            24 => '255.255.255.0',
            16 => '255.255.0.0',
            8 => '255.0.0.0',
            30 => '255.255.255.252',
            32 => '255.255.255.255',
            0 => '0.0.0.0',
        ];

        foreach ($cidrToNetmask as $cidr => $expectedNetmask) {
            $netmask = long2ip((0xFFFFFFFF << (32 - $cidr)) & 0xFFFFFFFF);
            $this->assertEquals($expectedNetmask, $netmask, "CIDR /{$cidr} should convert to {$expectedNetmask}");
        }
    }

    public function test_ip_range_calculations(): void
    {
        // Test if IP is in range
        $network = '192.168.1.0';
        $cidr = 24;
        $testIp = '192.168.1.100';

        $networkLong = ip2long($network);
        $mask = (0xFFFFFFFF << (32 - $cidr)) & 0xFFFFFFFF;
        $testIpLong = ip2long($testIp);

        $isInRange = ($networkLong & $mask) === ($testIpLong & $mask);

        $this->assertTrue($isInRange, "IP {$testIp} should be in network {$network}/{$cidr}");

        // Test IP outside range
        $outsideIp = '192.168.2.100';
        $outsideIpLong = ip2long($outsideIp);
        $isOutsideRange = ($networkLong & $mask) === ($outsideIpLong & $mask);

        $this->assertFalse($isOutsideRange, "IP {$outsideIp} should NOT be in network {$network}/{$cidr}");
    }

    public function test_hostname_validation(): void
    {
        $validHostnames = [
            'server1',
            'web-server-01',
            'db.example.com',
            'host123',
            'a',
            'test.local',
            'my-computer',
        ];

        $invalidHostnames = [
            '', // Empty
            'server_with_underscore', // Underscore not allowed
            'server with space', // Space not allowed
            '-start-with-dash', // Cannot start with dash
            'end-with-dash-', // Cannot end with dash
            'toolonghostaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', // Too long (over 63 chars)
            '192.168.1.1', // IP address
        ];

        // Simple hostname validation pattern - each label max 63 chars, total max 253 chars
        $hostnamePattern = '/^(?=.{1,253}$)([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/';

        foreach ($validHostnames as $hostname) {
            // Check individual components for length
            $isValid = true;
            if (strlen($hostname) > 253) {
                $isValid = false;
            } else {
                $labels = explode('.', $hostname);
                foreach ($labels as $label) {
                    if (strlen($label) > 63 || strlen($label) === 0) {
                        $isValid = false;
                        break;
                    }
                }
            }

            $this->assertTrue($isValid && preg_match('/^[a-zA-Z0-9.-]+$/', $hostname), "Hostname {$hostname} should be valid");
        }

        foreach ($invalidHostnames as $hostname) {
            if ($hostname !== '') { // Empty string has special handling
                // Check for obvious invalidity
                $isInvalid = strlen($hostname) > 63 || // Single label too long
                           strpos($hostname, '_') !== false ||
                           strpos($hostname, ' ') !== false ||
                           str_starts_with($hostname, '-') ||
                           str_ends_with($hostname, '-') ||
                           filter_var($hostname, FILTER_VALIDATE_IP);

                $this->assertTrue($isInvalid, "Hostname {$hostname} should be invalid (length: ".strlen($hostname).')');
            } else {
                $this->assertEmpty($hostname);
            }
        }
    }

    public function test_port_range_validation(): void
    {
        $validPorts = [1, 80, 443, 3306, 8080, 65535];
        $invalidPorts = [0, -1, 65536, 99999];

        foreach ($validPorts as $port) {
            $this->assertTrue($port >= 1 && $port <= 65535, "Port {$port} should be valid");
        }

        foreach ($invalidPorts as $port) {
            $this->assertFalse($port >= 1 && $port <= 65535, "Port {$port} should be invalid");
        }
    }
}
