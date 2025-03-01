
# Laravel SDK for SUI MOVE Blockchain (`mclever/larasui-sdk`)

A comprehensive, Laravel-friendly SDK for interacting with the Sui blockchain using Move smart contracts. Built for developers to seamlessly integrate Sui’s features—token creation, NFT minting, staking, transaction batching, and more—into their Laravel applications.

![License](https://img.shields.io/badge/License-MIT-blue.svg) ![Latest Version on Packagist](https://img.shields.io/packagist/v/mclever/larasui-sdk.svg)

## Requirements

- PHP: ^8.0

- Laravel: ^10.0

- Frontend wallet integration (e.g., @mysten/sui.js) for signing/executing transactions.

---

## Table of Contents
1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Usage](#usage)
   - [Connecting to a Wallet](#connecting-to-a-wallet)
   - [Fetching Balances](#fetching-balances)
   - [Creating Tokens](#creating-tokens)
   - [Creating NFTs](#creating-nfts)
   - [Executing Move Calls](#executing-move-calls)
   - [Transferring Assets](#transferring-assets)
   - [Staking SUI](#staking-sui)
   - [Fetching Transaction Details](#fetching-transaction-details)
   - [Fetching Events](#fetching-events)
4. [API Reference](#api-reference)
5. [Contributing](#contributing)
6. [License](#license)

---

## Installation

Install the package via Composer:

```bash
composer require mclever/larasui-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="config"

```

This will create a `sui.php` configuration file in your `config` directory.

---

## Configuration

Update the `config/sui.php` file with your SUI RPC URL:

```php
return [
    'rpc_url' => env('SUI_RPC_URL', 'https://fullnode.devnet.sui.io:443'),
];
```

You can also set the `SUI_RPC_URL` in your `.env` file:

```env
SUI_RPC_URL=https://fullnode.devnet.sui.io:443
```

---

## Usage

### Connecting to a Wallet
To connect to a wallet, use the `connectWallet` method. This method prepares the wallet connection, but the actual connection must be completed by the frontend (e.g., using `@mysten/sui.js`).

```php
use Mclever\LarasuiSdk\Facades\Sui;

$walletAddress = Sui::connectWallet();
```

### Fetching Balances
Fetch the balance of an address for a specific coin type (default is SUI):

```php
$balance = Sui::getBalance('0xYourAddress');
echo "Balance: {$balance} SUI";
```

### Creating Tokens
Create a new fungible token:

```php
$txDigest = Sui::createToken(
    'MyToken',
    'MTK',
    6, // Decimals
    '0xYourAddress',
    1000000, // Initial supply
    10000 // Gas budget
);
echo "Token creation transaction digest: {$txDigest}";
```

### Creating NFTs
Create a new NFT:

```php
$txDigest = Sui::createNFT(
    'MyNFT',
    'A unique NFT',
    'https://example.com/nft-metadata.json',
    '0xYourAddress',
    10000 // Gas budget
);
echo "NFT creation transaction digest: {$txDigest}";
```

### Executing Move Calls
Execute a Move call:

```php
$txDigest = Sui::executeMoveCall(
    '0xPackageId',
    'module_name',
    'function_name',
    [], // Type arguments
    ['arg1', 'arg2'], // Function arguments
    '0xYourAddress',
    10000 // Gas budget
);
echo "Move call transaction digest: {$txDigest}";
```

### Transferring Assets
Transfer coins or objects to another address:

```php
$txDigest = Sui::transfer(
    '0xYourAddress',
    '0xRecipientAddress',
    '0xObjectId',
    10000 // Gas budget
);
echo "Transfer transaction digest: {$txDigest}";
```

### Staking SUI
Stake SUI to a validator:

```php
$txDigest = Sui::stakeSui(
    '0xYourAddress',
    '0xValidatorAddress',
    '0xCoinId',
    1000000000, // Amount to stake
    10000 // Gas budget
);
echo "Staking transaction digest: {$txDigest}";
```

### Fetching Transaction Details
Get details for a transaction by its digest:

```php
$transaction = Sui::getTransaction('0xTransactionDigest');
print_r($transaction);
```

### Fetching Events
Fetch recent events of a specific type:

```php
$events = Sui::getEvents('0xPackage::module::Event', 10);
print_r($events);
```

---

## API Reference

### `SuiClient` Methods
- **`connectWallet()`**: Prepares wallet connection.
- **`getBalance(string $address, ?string $coinType = '0x2::sui::SUI')`**: Fetches the balance of an address.
- **`createToken(string $name, string $symbol, int $decimals, string $sender, int $initialSupply, int $gasBudget = 10000)`**: Creates a new fungible token.
- **`createNFT(string $name, string $description, string $uri, string $sender, int $gasBudget = 10000)`**: Creates a new NFT.
- **`executeMoveCall(string $packageId, string $module, string $function, array $typeArgs, array $args, string $sender, ?int $gasBudget = null)`**: Executes a Move call.
- **`transfer(string $sender, string $recipient, string $objectId, int $gasBudget = 10000)`**: Transfers coins or objects.
- **`stakeSui(string $sender, string $validatorAddress, string $coinId, int $amount, ?int $gasBudget = null)`**: Stakes SUI to a validator.
- **`getTransaction(string $txDigest)`**: Fetches transaction details.
- **`getEvents(string $eventType, int $limit = 10)`**: Fetches recent events.
- **`getCoinMetadata(string $coinType)`**: Fetches metadata for a coin type.

For a full list of methods, refer to the [SuiClient class](#suiclient-class).

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Submit a pull request.

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Acknowledgements

Built by [Mr clever](https://x.com/mr_clever4) with  for the Sui and Laravel communities.

---

## Support

For questions or issues, please open an issue on [GitHub](https://github.com/mclever7/larasui-sdk/issues).

---

Happy coding! 🚀
