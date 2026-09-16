<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Support\Tenancy\CurrentOrganization;
use RuntimeException;
use Tests\TestCase;

class CurrentOrganizationTest extends TestCase
{
    public function test_nested_operations_restore_their_callers_context_and_return_value(): void
    {
        $context = new CurrentOrganization;
        $first = new Organization;
        $second = new Organization;
        $result = $context->run($first, function () use ($context, $first, $second) {
            $this->assertSame($first, $context->get());
            $context->run($second, fn () => $this->assertSame($second, $context->get()));
            $this->assertSame($first, $context->get());

            return 'completed';
        });
        $this->assertSame('completed', $result);
        $this->assertFalse($context->has());
    }

    public function test_failure_restores_existing_context(): void
    {
        $context = new CurrentOrganization;
        $original = new Organization;
        $context->set($original);
        try {
            $context->run(new Organization, fn () => throw new RuntimeException('failure'));
            $this->fail('An exception must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('failure', $exception->getMessage());
        }
        $this->assertSame($original, $context->get());
    }

    public function test_failure_clears_context_when_caller_had_none(): void
    {
        $context = new CurrentOrganization;
        try {
            $context->run(new Organization, fn () => throw new RuntimeException('failure'));
        } catch (RuntimeException) {
            $this->assertFalse($context->has());
        }
        $this->assertFalse($context->has());
    }
}
