<?php
/**
 * This file is part of the prooph/pdo-event-store.
 * (c) 2016-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSimpleStreamStrategy;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PdoEventStoreProjector;
use Prooph\EventStore\Projection\ReadModel;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Pdo\TestUtil;

require __DIR__ . '/../../vendor/autoload.php';

$readModel = new class() implements ReadModel {
    public function init(): void
    {
    }

    public function isInitialized(): bool
    {
        return true;
    }

    public function reset(): void
    {
    }

    public function delete(): void
    {
    }

    public function stack(string $operation, ...$args): void
    {
    }

    public function persist(): void
    {
    }
};

$connection = TestUtil::getConnection();
TestUtil::initDefaultDatabaseTables($connection);

$eventStore = new MySqlEventStore(
    new FQCNMessageFactory(),
    $connection,
    new MySqlSimpleStreamStrategy()
);

$projectionManager = new MySqlProjectionManager(
    $eventStore,
    $connection
);
$projection = $projectionManager->createReadModelProjection(
    'test_projection',
    $readModel,
    [
        PdoEventStoreProjector::OPTION_PCNTL_DISPATCH => true,
    ]
);
pcntl_signal(SIGQUIT, function () use ($projection) {
    $projection->stop();
    exit(SIGUSR1);
});
$projection
    ->fromStream('user-123')
    ->when([
        UserCreated::class => function (array $state, UserCreated $event): array {
            return $state;
        },
    ])
    ->run();
