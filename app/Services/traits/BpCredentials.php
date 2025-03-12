<?php

namespace App\Services\traits;

trait BpCredentials
{
    public string $botpressUrl;
    public string $apiKey;
    public string $clientId;
    public string $botId;

    protected array $bpAccounts = [
        'botpress@communitasker.io' => [
            'integration_id' => '1b7ba5bf-13b2-4a42-92cb-ebf2094ef3e9',
            'api_key' => 'bp_pat_tPxHyjOAma3uuSAgcAAwrSGz4Dz0emlxQiwi',
            'workspace_id' => 'wkspace_01JNZCC6FDDH0E2WY72HW1R47H',
            'client_id' => 'b80d486f-165d-4232-b0a3-144652eadb78',
        ],
        'botpress1@communitasker.io' => [
            'integration_id' => '41efea3f-2b34-475e-8bd6-34cf801e0ef3',
            'api_key' => 'bp_pat_z9Y01qqS8ovWx8kZrmLdgkJ6k9wNVtgKNHev',
            'workspace_id' => 'wkspace_01JNZYEWC54YPSHXV3FCSCQCZR',
            'client_id' => 'd9b700fb-0b44-4c5f-85f6-fcad1b8ab3e0',
        ],
        'arashrostamichatgpt4@gmail.com' => [
            'integration_id' => '0c9b1930-a01d-4402-a4f6-f8ce53560eef',
            'api_key' => 'bp_pat_Jm5KZKQu0AUyxi26TAbU4nA0bIQ5WD92cnlw',
            'workspace_id' => 'wkspace_01JNA07X3RYEQZ9MDZJMR9K71Z',
            'client_id' => '64683c0e-ec44-4e8b-84e8-3e751b477b22',
        ],
    ];

    public function initializeBpCredentials(): void
    {
        $randomEmail = array_rand($this->bpAccounts);

        $this->apiKey = $this->bpAccounts[$randomEmail]['api_key'];
        $this->clientId = $this->bpAccounts[$randomEmail]['client_id'];
        $this->botId = $this->bpAccounts[$randomEmail]['integration_id'];
        $this->botpressUrl = env('BOTPRESS_API_URL');
    }
}
