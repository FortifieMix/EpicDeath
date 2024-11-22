<?php

namespace Taskov1ch\EpicDeath;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\SmokeParticle;

class Main extends PluginBase
{
	public array $cooldowns = [];
	public array $process = [];
	private array $config;

	protected function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventsListener($this), $this);
		$this->config = $this->getConfig()->getAll();
	}

	protected function onDisable(): void
	{
		foreach ($this->process as $player) {
			$this->unsetProcess($player);
			$player->kill();
		}
	}

	private function sendSound(array $sounds, Player $player): void
	{
		$pos = $player->getPosition();

		foreach ($sounds as $sound) {
			$pk = PlaySoundPacket::create(
				$sound, $pos->getX(), $pos->getY(), $pos->getZ(),
				$this->config["volume"], $this->config["pitch"]
			);

			foreach ($player->getWorld()->getPlayers() as $player) {
				$player->getNetworkSession()->sendDataPacket(clone $pk);
			}
		}
	}

	public function inProcess(Player $player): bool
	{
		return in_array($player, $this->process);
	}

	public function unsetProcess(Player $player): void
	{
		$player->setNoClientPredictions(false);
		unset($this->process[array_search($player, $this->process)]);
	}

	public function startProcess(Player $player): void
	{
		if ($this->inProcess($player)) {
			return;
		}

		$this->process[] = $player;
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setNoClientPredictions(true);
		$this->sendSound($this->config["start"], $player);
		$player->getEffects()->add(new EffectInstance(
			VanillaEffects::LEVITATION(), $this->config["duration"] * 20 + 10, 0, false
		));

		$task = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
			function () use ($player)
			{
				$player->getWorld()->addParticle(
					$player->getPosition(),
					new SmokeParticle(1)
				);
			}
		), 10);

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
			function () use ($player, $task)
			{
				$task->remove();
				$this->sendSound($this->config["end"], $player);
				$player->kill();
			}
		), $this->config["duration"] * 20);
	}
}