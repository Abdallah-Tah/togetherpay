<?php

use App\Models\GroupMember;
use App\Services\PaymentDrivers\CashAppDriver;
use App\Services\PaymentDrivers\VenmoDriver;

describe('VenmoDriver', function () {
    it('builds payment link using owner handle', function () {
        $member = new GroupMember([
            'group_id' => 1,
            'venmo_username' => '@membervenmo',
        ]);

        $driver = new VenmoDriver;
        $result = $driver->buildLink(
            ownerHandle: '@ownervenmo',
            member: $member,
            amountCents: 5000,
            prCode: 'PR123',
            note: 'Test Payment'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['uri', 'web_fallback'])
            ->and($result['uri'])->toContain('ownervenmo')
            ->and($result['uri'])->toContain('50.00')
            ->and($result['web_fallback'])->toContain('ownervenmo')
            ->and($result['web_fallback'])->toContain('50.00');
    });

    it('normalizes owner handle by removing @ prefix', function () {
        $member = new GroupMember(['group_id' => 1]);

        $driver = new VenmoDriver;
        $result = $driver->buildLink(
            ownerHandle: 'ownervenmo',  // Without @
            member: $member,
            amountCents: 2500,
            prCode: 'PR456',
            note: 'Test'
        );

        expect($result['uri'])->toContain('ownervenmo')
            ->and($result['web_fallback'])->toContain('ownervenmo');
    });

    it('validates venmo account format', function () {
        $driver = new VenmoDriver;

        expect($driver->validateAccount('@validuser'))->toBeTrue()
            ->and($driver->validateAccount('validuser'))->toBeTrue()
            ->and($driver->validateAccount('@user-name_123'))->toBeTrue()
            ->and($driver->validateAccount('@abc'))->toBeFalse() // Too short
            ->and($driver->validateAccount('ab'))->toBeFalse() // Too short
            ->and($driver->validateAccount('@user@invalid'))->toBeFalse(); // Invalid chars
    });
});

describe('CashAppDriver', function () {
    it('builds payment link using owner $cashtag', function () {
        $member = new GroupMember([
            'group_id' => 1,
            'cash_tag' => '$membercash',
        ]);

        $driver = new CashAppDriver;
        $result = $driver->buildLink(
            ownerHandle: '$ownercash',
            member: $member,
            amountCents: 7500,
            prCode: 'PR789',
            note: 'Test Payment'
        );

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['uri', 'web_fallback'])
            ->and($result['uri'])->toContain('ownercash')
            ->and($result['uri'])->toContain('75.00')
            ->and($result['web_fallback'])->toContain('ownercash')
            ->and($result['web_fallback'])->toContain('75.00');
    });

    it('normalizes owner cashtag by removing $ prefix', function () {
        $member = new GroupMember(['group_id' => 1]);

        $driver = new CashAppDriver;
        $result = $driver->buildLink(
            ownerHandle: 'ownercash',  // Without $
            member: $member,
            amountCents: 1000,
            prCode: 'PR100',
            note: 'Test'
        );

        // Should still work and build proper links
        expect($result['uri'])->toContain('cash.app')
            ->and($result['web_fallback'])->toContain('cash.app');
    });

    it('validates cash app account format', function () {
        $driver = new CashAppDriver;

        // Valid cashtags (1-20 alphanumeric chars)
        expect($driver->validateAccount('$validuser'))->toBeTrue()
            ->and($driver->validateAccount('$abc123'))->toBeTrue()
            ->and($driver->validateAccount('$CashTag'))->toBeTrue()
            ->and($driver->validateAccount('$a'))->toBeTrue() // Single char is valid
            // Valid emails
            ->and($driver->validateAccount('user@example.com'))->toBeTrue()
            // Valid phone
            ->and($driver->validateAccount('+14155551234'))->toBeTrue()
            ->and($driver->validateAccount('14155551234'))->toBeTrue()
            // Invalid
            ->and($driver->validateAccount('$toolongcashtagname123'))->toBeFalse() // Too long (>20)
            ->and($driver->validateAccount('invalid@'))->toBeFalse(); // Invalid email
    });
});

describe('PaymentDriverFactory', function () {
    it('creates venmo driver', function () {
        $driver = \App\Services\PaymentDriverFactory::make('venmo');

        expect($driver)->toBeInstanceOf(VenmoDriver::class)
            ->and($driver->getName())->toBe('venmo');
    });

    it('creates cash app driver with multiple naming formats', function () {
        $driver1 = \App\Services\PaymentDriverFactory::make('cash_app');
        $driver2 = \App\Services\PaymentDriverFactory::make('cashapp');

        expect($driver1)->toBeInstanceOf(CashAppDriver::class)
            ->and($driver2)->toBeInstanceOf(CashAppDriver::class)
            ->and($driver1->getName())->toBe('cash_app');
    });

    it('throws exception for unsupported payment method', function () {
        \App\Services\PaymentDriverFactory::make('paypal');
    })->throws(\InvalidArgumentException::class, 'Unsupported payment method: paypal');

    it('lists available drivers', function () {
        $available = \App\Services\PaymentDriverFactory::available();

        expect($available)->toBeArray()
            ->and($available)->toContain('cashapp')
            ->and($available)->toContain('venmo');
    });

    it('checks if payment method is supported', function () {
        expect(\App\Services\PaymentDriverFactory::supports('venmo'))->toBeTrue()
            ->and(\App\Services\PaymentDriverFactory::supports('VENMO'))->toBeTrue()
            ->and(\App\Services\PaymentDriverFactory::supports('cashapp'))->toBeTrue()
            ->and(\App\Services\PaymentDriverFactory::supports('CASHAPP'))->toBeTrue()
            ->and(\App\Services\PaymentDriverFactory::supports('paypal'))->toBeFalse()
            ->and(\App\Services\PaymentDriverFactory::supports('zelle'))->toBeFalse();
    });
});
