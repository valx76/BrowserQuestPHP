<?php

namespace App\Command;

use App\Controller\WebsocketController;
use App\Game\World;
use App\Service\KindResolver;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'BrowserQuestServer',
    description: 'Start the game server',
)]
class BrowserQuestServerCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/')]
        private readonly string $publicPath,

        #[Autowire('%env(int:APP_PORT)%')]
        private readonly int $port,

        #[Autowire('%env(int:APP_WORLDS_COUNT)%')]
        private readonly int $worldsCount,

        #[Autowire('%env(int:APP_MAX_PLAYERS_PER_WORLD)%')]
        private readonly int $maxPlayersPerWorld,

        private readonly WebsocketController $websocketController,
        private readonly KindResolver $kindResolver,
        string $name = null
    )
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mapFile = sprintf('%s/maps/world_server.json', $this->publicPath);

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this->websocketController
                )
            ),
            $this->port
        );

        $worlds = [];

        for ($i = 0; $i < $this->worldsCount; $i++) {
            $worlds[$i] = new World($this->kindResolver, $i, $this->maxPlayersPerWorld);
            $worlds[$i]->run($mapFile);
        }

        $this->websocketController->setWorlds($worlds);

        $server->run();

        return Command::SUCCESS;
    }
}