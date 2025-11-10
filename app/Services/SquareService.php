<?php

namespace App\Services;

use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Models\CreateCustomerRequest;
use Square\Models\CreateSubscriptionRequest;
use Square\Models\Money;
use Square\Models\Phase;
use Square\Models\SearchCustomersRequest;
use Square\Models\SubscriptionCadence;
use Square\Models\SubscriptionPricingType;
use Square\SquareClient;

class SquareService
{
    protected SquareClient $client;

    protected string $locationId;

    public function __construct(?string $accessToken = null)
    {
        $environment = config('square.environment') === 'production'
            ? Environment::PRODUCTION
            : Environment::SANDBOX;

        $this->client = new SquareClient([
            'accessToken' => $accessToken ?? config('square.access_token'),
            'environment' => $environment,
        ]);

        $this->locationId = config('square.location_id');
    }

    /**
     * Generate OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $baseUrl = config('square.environment') === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';

        $params = http_build_query([
            'client_id' => config('square.application_id'),
            'scope' => implode(' ', config('square.oauth.scopes')),
            'session' => 'false',
            'state' => $state,
        ]);

        return "{$baseUrl}/oauth2/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     */
    public function obtainAccessToken(string $code): array
    {
        try {
            $response = $this->client->getOAuthApi()->obtainToken(
                new \Square\Models\ObtainTokenRequest(
                    config('square.application_id'),
                    config('square.application_secret'),
                    'authorization_code',
                    null,
                    null,
                    null,
                    $code
                )
            );

            if ($response->isSuccess()) {
                $result = $response->getResult();

                return [
                    'access_token' => $result->getAccessToken(),
                    'refresh_token' => $result->getRefreshToken(),
                    'expires_at' => $result->getExpiresAt(),
                    'merchant_id' => $result->getMerchantId(),
                ];
            }

            throw new \Exception('Failed to obtain access token: '.$response->getErrors()[0]->getDetail());
        } catch (ApiException $e) {
            Log::error('Square OAuth error', ['error' => $e->getMessage()]);
            throw new \Exception('Square OAuth failed: '.$e->getMessage());
        }
    }

    /**
     * Get merchant locations
     */
    public function getLocations(): array
    {
        try {
            $response = $this->client->getLocationsApi()->listLocations();

            if ($response->isSuccess()) {
                $locations = [];
                foreach ($response->getResult()->getLocations() as $location) {
                    $locations[] = [
                        'id' => $location->getId(),
                        'name' => $location->getName(),
                        'address' => $location->getAddress()?->getAddressLine1(),
                        'status' => $location->getStatus(),
                    ];
                }

                return $locations;
            }

            return [];
        } catch (ApiException $e) {
            Log::error('Square locations error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Create a Square customer for a group member
     */
    public function createCustomer(GroupMember $member): ?string
    {
        try {
            $request = new CreateCustomerRequest;
            $request->setGivenName($member->user->name);
            $request->setEmailAddress($member->user->email);
            $request->setReferenceId("member_{$member->id}");

            if ($member->user->phone) {
                $request->setPhoneNumber($member->user->phone);
            }

            $response = $this->client->getCustomersApi()->createCustomer($request);

            if ($response->isSuccess()) {
                $customerId = $response->getResult()->getCustomer()->getId();
                Log::info('Square customer created', [
                    'member_id' => $member->id,
                    'customer_id' => $customerId,
                ]);

                return $customerId;
            }

            Log::error('Failed to create Square customer', [
                'member_id' => $member->id,
                'errors' => $response->getErrors(),
            ]);

            return null;
        } catch (ApiException $e) {
            Log::error('Square create customer error', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search for existing customer by email
     */
    public function findCustomerByEmail(string $email): ?string
    {
        try {
            $query = new \Square\Models\CustomerQuery;
            $filter = new \Square\Models\CustomerFilter;
            $filter->setEmailAddress(new \Square\Models\CustomerTextFilter($email));
            $query->setFilter($filter);

            $request = new SearchCustomersRequest;
            $request->setQuery($query);

            $response = $this->client->getCustomersApi()->searchCustomers($request);

            if ($response->isSuccess() && $response->getResult()->getCustomers()) {
                return $response->getResult()->getCustomers()[0]->getId();
            }

            return null;
        } catch (ApiException $e) {
            Log::error('Square search customer error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create a recurring subscription for a member
     */
    public function createSubscription(
        string $customerId,
        int $amountCents,
        string $currency = 'USD',
        string $cadence = 'MONTHLY',
        ?\DateTime $startDate = null
    ): ?string {
        try {
            // Create subscription phases
            $money = new Money;
            $money->setAmount($amountCents);
            $money->setCurrency($currency);

            $phase = new Phase;
            $phase->setCadence($cadence);
            $phase->setRecurringPriceMoney($money);
            $phase->setPeriods(null); // Infinite until cancelled

            $request = new CreateSubscriptionRequest(
                $this->locationId,
                $customerId
            );
            $request->setIdempotencyKey(uniqid('sub_', true));
            $request->setPlanVariationData(new \Square\Models\SubscriptionPlanVariationData([
                new \Square\Models\SubscriptionPhase([
                    'cadence' => $cadence,
                    'recurring_price_money' => $money,
                ]),
            ]));
            $request->setPricingType(SubscriptionPricingType::STATIC);

            if ($startDate) {
                $request->setStartDate($startDate->format('Y-m-d'));
            }

            $response = $this->client->getSubscriptionsApi()->createSubscription($request);

            if ($response->isSuccess()) {
                $subscriptionId = $response->getResult()->getSubscription()->getId();
                Log::info('Square subscription created', [
                    'customer_id' => $customerId,
                    'subscription_id' => $subscriptionId,
                    'amount_cents' => $amountCents,
                ]);

                return $subscriptionId;
            }

            Log::error('Failed to create Square subscription', [
                'customer_id' => $customerId,
                'errors' => $response->getErrors(),
            ]);

            return null;
        } catch (ApiException $e) {
            Log::error('Square create subscription error', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            $response = $this->client->getSubscriptionsApi()->cancelSubscription($subscriptionId);

            if ($response->isSuccess()) {
                Log::info('Square subscription cancelled', [
                    'subscription_id' => $subscriptionId,
                ]);

                return true;
            }

            Log::error('Failed to cancel Square subscription', [
                'subscription_id' => $subscriptionId,
                'errors' => $response->getErrors(),
            ]);

            return false;
        } catch (ApiException $e) {
            Log::error('Square cancel subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Pause a subscription
     */
    public function pauseSubscription(string $subscriptionId): bool
    {
        try {
            $response = $this->client->getSubscriptionsApi()->pauseSubscription($subscriptionId);

            if ($response->isSuccess()) {
                Log::info('Square subscription paused', [
                    'subscription_id' => $subscriptionId,
                ]);

                return true;
            }

            return false;
        } catch (ApiException $e) {
            Log::error('Square pause subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Resume a paused subscription
     */
    public function resumeSubscription(string $subscriptionId): bool
    {
        try {
            $response = $this->client->getSubscriptionsApi()->resumeSubscription($subscriptionId);

            if ($response->isSuccess()) {
                Log::info('Square subscription resumed', [
                    'subscription_id' => $subscriptionId,
                ]);

                return true;
            }

            return false;
        } catch (ApiException $e) {
            Log::error('Square resume subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        try {
            $response = $this->client->getSubscriptionsApi()->retrieveSubscription($subscriptionId);

            if ($response->isSuccess()) {
                $subscription = $response->getResult()->getSubscription();

                return [
                    'id' => $subscription->getId(),
                    'status' => $subscription->getStatus(),
                    'customer_id' => $subscription->getCustomerId(),
                    'location_id' => $subscription->getLocationId(),
                    'start_date' => $subscription->getStartDate(),
                    'charged_through_date' => $subscription->getChargedThroughDate(),
                    'invoice_ids' => $subscription->getInvoiceIds(),
                ];
            }

            return null;
        } catch (ApiException $e) {
            Log::error('Square get subscription error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $body, string $signature, string $url): bool
    {
        $signatureKey = config('square.webhook_signature_key');

        if (! $signatureKey) {
            Log::warning('Square webhook signature key not configured');

            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $url.$body, $signatureKey, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create client with user's access token
     */
    public static function forUser(User $user): self
    {
        if (! $user->square_access_token) {
            throw new \Exception('User does not have Square connected');
        }

        return new self($user->square_access_token);
    }
}
