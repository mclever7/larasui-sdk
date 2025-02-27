<?php

namespace Mclever\LarasuiSdk;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SuiClient
{
    protected string $rpcUrl;
    protected array $coinMetadata = [];

    /**
     * Constructor - Initialize RPC URL from configuration
     */
    public function __construct(?string $rpcUrl = null)
    {
        $this->rpcUrl = $rpcUrl ?? config('sui.rpc_url');
    }

    /**
     * Execute a Sui JSON-RPC API call with error handling
     *
     * @param string $method RPC method name
     * @param array $params Parameters for the RPC call
     * @return mixed|null API result or null on non-critical failure
     * @throws Exception
     */
    private function callSuiAPI(string $method, array $params): mixed
    {
        try {
            $response = Http::post($this->rpcUrl, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => $method,
                'params' => $params,
            ]);

            if ($response->failed()) {
                throw new Exception("{$method}: HTTP request failed with status {$response->status()}");
            }

            $data = $response->json();
            if (isset($data['error'])) {
                throw new Exception("{$method}: " . ($data['error']['message'] ?? 'Unknown error'));
            }

            return $data['result'] ?? null;
        } catch (Exception $e) {
            Log::error("SuiClient API Call Failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Estimate gas cost for a transaction or set of transactions
     *
     * @param string $sender Sender address
     * @param array $transactions Array of transaction data
     * @return int Estimated gas cost in MIST
     * @throws Exception
     */
    public function estimateGas(string $sender, array $transactions): int
    {
        $result = $this->callSuiAPI('sui_dryRunTransactionBlock', [
            ['sender' => $sender, 'transactions' => $transactions],
            ['showEffects' => true],
        ]);

        if (!$result || !isset($result['effects']['gasUsed'])) {
            throw new Exception('Failed to estimate gas fees');
        }

        return (int) ($result['effects']['gasUsed']['computationCost'] + $result['effects']['gasUsed']['storageCost']);
    }

    /**
     * Execute a batch of transactions
     *
     * @param string $sender Sender address
     * @param array $transactions Array of transactions to batch
     * @param int|null $gasBudget Optional gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function executeBatchTransactions(string $sender, array $transactions, ?int $gasBudget = null): string
    {
        $gasBudget ??= $this->estimateGas($sender, $transactions);

        $result = $this->callSuiAPI('sui_executeTransactionBlock', [
            '0xPendingBatchTx', // Placeholder - frontend signs batch tx
            ['showEffects'],
            $sender,
            $gasBudget,
        ]);

        if (!$result || !isset($result['digest'])) {
            throw new Exception('Failed to prepare batch transactions');
        }

        return $result['digest'];
    }

    /**
     * Prepare wallet connection - requires frontend integration
     *
     * @return string Placeholder wallet address
     */
    public function connectWallet(): string
    {
        return '0xPendingWalletAddress'; // Frontend JS (e.g., @mysten/sui.js) completes
    }

    /**
     * Get balance for any coin type at an address
     *
     * @param string $address Sui address
     * @param string|null $coinType Coin type (e.g., '0x2::sui::SUI')
     * @return float Decimal-adjusted balance
     * @throws Exception
     */
    public function getBalance(string $address, ?string $coinType = '0x2::sui::SUI'): float
    {
        $result = $this->callSuiAPI('suix_getBalance', [$address, $coinType]);
        if (!$result || !isset($result['totalBalance'])) {
            throw new Exception("No balance data returned for {$coinType}");
        }

        $decimals = $this->getDecimalsForCoinType($coinType);
        return $result['totalBalance'] / pow(10, $decimals);
    }

    /**
     * Create a new fungible token
     *
     * @param string $name Token name
     * @param string $symbol Token symbol
     * @param int $decimals Number of decimal places
     * @param string $sender Sender address
     * @param int $initialSupply Initial supply amount
     * @param int $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function createToken(
        string $name,
        string $symbol,
        int $decimals,
        string $sender,
        int $initialSupply,
        int $gasBudget = 10000
    ): string {
        $args = [$name, $symbol, $decimals, $initialSupply];
        $result = $this->callSuiAPI('sui_moveCall', [
            $sender,
            '0x2',
            'coin',
            'create',
            [],
            $args,
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare token creation');
        }
        return 'pending_tx_digest';
    }

    /**
     * Create a new NFT
     *
     * @param string $name NFT name
     * @param string $description NFT description
     * @param string $uri NFT URI (e.g., IPFS link)
     * @param string $sender Sender address
     * @param int $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function createNFT(
        string $name,
        string $description,
        string $uri,
        string $sender,
        int $gasBudget = 10000
    ): string {
        $result = $this->callSuiAPI('sui_moveCall', [
            $sender,
            '0x2',
            'nft',
            'mint_to_sender',
            [],
            [$name, $description, $uri],
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare NFT creation');
        }
        return 'pending_tx_digest';
    }

    /**
     * Execute a Move call
     *
     * @param string $packageId Package ID
     * @param string $module Module name
     * @param string $function Function name
     * @param array $typeArgs Type arguments
     * @param array $args Function arguments
     * @param string $sender Sender address
     * @param int|null $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function executeMoveCall(
        string $packageId,
        string $module,
        string $function,
        array $typeArgs,
        array $args,
        string $sender,
        ?int $gasBudget = null
    ): string {
        $gasBudget ??= $this->estimateGas($sender, [['MoveCall', [$packageId, $module, $function, $typeArgs, $args]]]);
        $result = $this->callSuiAPI('sui_moveCall', [
            $sender,
            $packageId,
            $module,
            $function,
            $typeArgs,
            $args,
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare Move call');
        }
        return 'pending_tx_digest';
    }

    /**
     * Execute a signed transaction
     *
     * @param string $signedTx Signed transaction data
     * @param string $sender Sender address
     * @param int $gasBudget Gas budget in MIST
     * @return string Transaction digest
     * @throws Exception
     */
    public function executeSignedTransaction(string $signedTx, string $sender, int $gasBudget = 10000): string
    {
        $result = $this->callSuiAPI('sui_executeTransactionBlock', [
            $signedTx,
            ['showEffects'],
            $sender,
            $gasBudget,
        ]);

        if (!$result || !isset($result['digest'])) {
            throw new Exception('Failed to execute signed transaction');
        }
        return $result['digest'];
    }

    /**
     * Get owned objects (e.g., NFTs) for an address
     *
     * @param string $address Sui address
     * @param string|null $structType Optional struct type filter
     * @return array List of owned objects
     * @throws Exception
     */
    public function getOwnedObjects(string $address, ?string $structType = null): array
    {
        $params = [$address];
        if ($structType) {
            $params[] = ['filter' => ['StructType' => $structType]];
        }
        $result = $this->callSuiAPI('suix_getOwnedObjects', $params);
        return $result['data'] ?? [];
    }

    /**
     * Get recent events
     *
     * @param string $eventType Event type (e.g., '0xPackage::module::Event')
     * @param int $limit Number of events to fetch
     * @return array List of events
     * @throws Exception
     */
    public function getEvents(string $eventType, int $limit = 10): array
    {
        $result = $this->callSuiAPI('sui_getEvents', [
            ['EventType' => $eventType],
            null,
            $limit,
            true,
        ]);
        return $result ?? [];
    }

    /**
     * Get transaction details by digest
     *
     * @param string $txDigest Transaction digest
     * @return array Transaction details
     * @throws Exception
     */
    public function getTransaction(string $txDigest): array
    {
        $result = $this->callSuiAPI('sui_getTransactionBlock', [$txDigest, ['showEffects' => true]]);
        return $result ?? [];
    }

    /**
     * Get all coins owned by an address
     *
     * @param string $address Sui address
     * @return array List of coins
     * @throws Exception
     */
    public function getAllCoins(string $address): array
    {
        $result = $this->callSuiAPI('suix_getAllCoins', [$address]);
        if (!$result || !isset($result['data'])) {
            throw new Exception('Failed to fetch all coins');
        }
        return $result['data'];
    }

    /**
     * Transfer coins or objects to another address
     *
     * @param string $sender Sender address
     * @param string $recipient Recipient address
     * @param string $objectId Object ID to transfer
     * @param int $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function transfer(string $sender, string $recipient, string $objectId, int $gasBudget = 10000): string
    {
        $result = $this->callSuiAPI('sui_transferObject', [
            $sender,
            $objectId,
            null,
            $gasBudget,
            $recipient,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare transfer');
        }
        return 'pending_tx_digest';
    }

    /**
     * Split coins into multiple amounts
     *
     * @param string $sender Sender address
     * @param string $coinId Coin object ID
     * @param array $amounts Amounts to split into
     * @param int $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function splitCoin(string $sender, string $coinId, array $amounts, int $gasBudget = 10000): string
    {
        $result = $this->callSuiAPI('sui_splitCoins', [
            $sender,
            $coinId,
            $amounts,
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare coin split');
        }
        return 'pending_tx_digest';
    }

    /**
     * Stake SUI to a validator
     *
     * @param string $sender Sender address
     * @param string $validatorAddress Validator address
     * @param string $coinId Coin object ID
     * @param int $amount Amount to stake
     * @param int|null $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function stakeSui(string $sender, string $validatorAddress, string $coinId, int $amount, ?int $gasBudget = null): string
    {
        $gasBudget ??= $this->estimateGas($sender, [['MoveCall', ['0x3', 'staking_pool', 'request_add_stake', [], [$validatorAddress, $coinId, $amount]]]]);
        $result = $this->callSuiAPI('sui_moveCall', [
            $sender,
            '0x3',
            'staking_pool',
            'request_add_stake',
            [],
            [$validatorAddress, $coinId, $amount],
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare staking SUI');
        }
        return 'pending_tx_digest';
    }

    /**
     * Withdraw staked SUI from a validator
     *
     * @param string $sender Sender address
     * @param string $stakedSuiId Staked SUI object ID
     * @param int|null $gasBudget Gas budget in MIST
     * @return string Transaction digest (pending until signed)
     * @throws Exception
     */
    public function withdrawStake(string $sender, string $stakedSuiId, ?int $gasBudget = null): string
    {
        $gasBudget ??= $this->estimateGas($sender, [['MoveCall', ['0x3', 'staking_pool', 'request_withdraw_stake', [], [$stakedSuiId]]]]);
        $result = $this->callSuiAPI('sui_moveCall', [
            $sender,
            '0x3',
            'staking_pool',
            'request_withdraw_stake',
            [],
            [$stakedSuiId],
            null,
            $gasBudget,
        ]);

        if (!$result) {
            throw new Exception('Failed to prepare withdrawal of staked SUI');
        }
        return 'pending_tx_digest';
    }

    /**
     * Get staking details for an address
     *
     * @param string $address Sui address
     * @return array Staking details
     * @throws Exception
     */
    public function getStakingDetails(string $address): array
    {
        $result = $this->callSuiAPI('suix_getStakes', [$address]);
        if (!$result) {
            throw new Exception('Failed to retrieve staking details');
        }
        return $result;
    }

    /**
     * Get coin metadata
     *
     * @param string $coinType Coin type
     * @return array Coin metadata
     * @throws Exception
     */
    public function getCoinMetadata(string $coinType): array
    {
        if (!isset($this->coinMetadata[$coinType])) {
            $result = $this->callSuiAPI('suix_getCoinMetadata', [$coinType]);
            if (!$result) {
                throw new Exception("Failed to fetch coin metadata for {$coinType}");
            }
            $this->coinMetadata[$coinType] = $result;
        }
        return $this->coinMetadata[$coinType];
    }

    /**
     * Get decimals for a coin type
     *
     * @param string $coinType Coin type
     * @return int Number of decimals
     * @throws Exception
     */
    protected function getDecimalsForCoinType(string $coinType): int
    {
        if ($coinType === '0x2::sui::SUI') {
            return 9;
        }
        $metadata = $this->getCoinMetadata($coinType);
        return $metadata['decimals'] ?? 6;
    }

    /**
     * Get the chain identifier
     *
     * @return string Chain ID (e.g., "mainnet")
     * @throws Exception
     */
    public function getChainIdentifier(): string
    {
        $result = $this->callSuiAPI('sui_getChainIdentifier', []);
        if (!$result) {
            throw new Exception('Failed to fetch chain identifier');
        }
        return $result;
    }

    /**
     * Get the latest checkpoint sequence number
     *
     * @return int Latest checkpoint sequence number
     * @throws Exception
     */
    public function getLatestCheckpointSequenceNumber(): int
    {
        $result = $this->callSuiAPI('sui_getLatestCheckpointSequenceNumber', []);
        if (!$result) {
            throw new Exception('Failed to fetch latest checkpoint sequence number');
        }
        return (int) $result;
    }

    /**
     * Get a list of checkpoints
     *
     * @param string|null $cursor Starting cursor (optional)
     * @param int $limit Number of checkpoints to fetch
     * @return array List of checkpoints
     * @throws Exception
     */
    public function getCheckpoints(?string $cursor = null, int $limit = 10): array
    {
        $result = $this->callSuiAPI('sui_getCheckpoints', [$cursor, $limit, true]);
        return $result ?? [];
    }

    /**
     * Get detailed information for a specific object
     *
     * @param string $objectId Object ID
     * @param array $options Additional options (e.g., ['showContent' => true])
     * @return array Object details
     * @throws Exception
     */
    public function getObject(string $objectId, array $options = []): array
    {
        $result = $this->callSuiAPI('sui_getObject', [$objectId, $options]);
        if (!$result) {
            throw new Exception('Failed to fetch object details');
        }
        return $result;
    }

    /**
     * Get details for multiple objects
     *
     * @param array $objectIds Array of object IDs
     * @param array $options Additional options
     * @return array List of object details
     * @throws Exception
     */
    public function multiGetObjects(array $objectIds, array $options = []): array
    {
        $result = $this->callSuiAPI('sui_multiGetObjects', [$objectIds, $options]);
        if (!$result) {
            throw new Exception('Failed to fetch multiple objects');
        }
        return $result;
    }

    /**
     * Get dynamic fields for an object
     *
     * @param string $objectId Object ID
     * @param string|null $cursor Starting cursor (optional)
     * @param int $limit Number of fields to fetch
     * @return array List of dynamic fields
     * @throws Exception
     */
    public function getDynamicFields(string $objectId, ?string $cursor = null, int $limit = 50): array
    {
        $result = $this->callSuiAPI('sui_getDynamicFields', [$objectId, $cursor, $limit]);
        return $result ?? [];
    }

    /**
     * Get total number of transaction blocks
     *
     * @return int Total transaction blocks
     * @throws Exception
     */
    public function getTotalTransactionBlocks(): int
    {
        $result = $this->callSuiAPI('sui_getTotalTransactionBlocks', []);
        if (!$result) {
            throw new Exception('Failed to fetch total transaction blocks');
        }
        return (int) $result;
    }

    /**
     * Get details for multiple transaction blocks
     *
     * @param array $txDigests Array of transaction digests
     * @param array $options Additional options
     * @return array List of transaction details
     * @throws Exception
     */
    public function multiGetTransactionBlocks(array $txDigests, array $options = []): array
    {
        $result = $this->callSuiAPI('sui_multiGetTransactionBlocks', [$txDigests, $options]);
        if (!$result) {
            throw new Exception('Failed to fetch multiple transaction blocks');
        }
        return $result;
    }

    /**
     * Get APY for all validators
     *
     * @return array Validator APY data
     * @throws Exception
     */
    public function getValidatorsApy(): array
    {
        $result = $this->callSuiAPI('sui_getValidatorsApy', []);
        if (!$result) {
            throw new Exception('Failed to fetch validators APY');
        }
        return $result;
    }
}
