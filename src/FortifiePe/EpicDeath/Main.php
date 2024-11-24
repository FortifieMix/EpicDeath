<?php

namespace FortifiePe\EpicDeath;

use pocketmine\entity\Effect;
use pocketmine\level\particle\EnchantmentTableParticle;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase
{
	private array $process = [];
	private array $cd = [];
	private array $config;

	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventsListener($this), $this);
		$this->config = $this->getConfig()->getAll();
	}

	public function onDisable(): void
	{
		foreach ($this->process as $player) {
			$this->removeFromProcess($player);
			$player->kill();
		}

		$this->process = [];
	}

	private function sendSound(array $sounds, Player $player): void
	{
		$pos = $player->getPosition();
		$worldPlayers = $player->getLevel()->getPlayers();

		array_map(function ($sound) use ($pos, $worldPlayers): void {
			$pk = new PlaySoundPacket();
			$pk->sound = $sound;
			$pk->x = $pos->getX();
			$pk->y = $pos->getY();
			$pk->z = $pos->getZ();
			$pk->volume = $this->config["volume"];
			$pk->float = $this->config["pitch"];
			array_map(fn(Player $p): bool => $p->dataPacket(clone $pk), $worldPlayers);
		}, $sounds);
	}

	public function inCd(Player $player): bool
	{
		return time() <= ($this->cd[strtolower($player->getName())] ?? 0);
	}

	public function inProcess(Player $player): bool
	{
		return in_array($player, $this->process, true);
	}

	public function startProcess(Player $player): void
	{
		if ($this->inProcess($player)) return;

		$this->process[] = $player;
		$this->sendSound($this->config["start"], $player);
		$lockPos = $player->getPosition();

		$task = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
			function () use ($player, $lockPos): void
			{
				$player->addEffect(
					Effect::getEffectByName("levitation")->setDuration(20)->setAmbient(0)->setVisible(false)
				);

				$pos = $player->getPosition();
				$lockPos->y = $pos->getY();
				$world = $player->getLevel();

				if ($lockPos->distanceSquared($pos) > 9) {
					$player->teleport($lockPos);
				}

				for ($i = 0; $i < 10; $i++) {
				    if (!$world) {
				        break;
				    }
	
					$world->addParticle(new EnchantmentTableParticle(
						$pos->add(mt_rand(-5, 5) / 10, mt_rand(-5, 5) / 10, mt_rand(-5, 5) / 10)
					));
				}
			}
		), 10);

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
			function () use ($player, $task): void {
			  $task->remove();
				$this->endProcess($player);
			}
		), $this->config["duration"] * 20);
	}

	private function endProcess(Player $player): void
	{
		$this->sendSound($this->config["end"], $player);

		for ($i = 0; $i < 10; $i++) {
			$player->getLevel()->addParticle(new MobSpawnParticle($player->getPosition(), 3, 3));
		}

		$player->kill();
		$this->removeFromProcess($player);
		$this->cd[strtolower($player->getName())] = time() + $this->config["cd"];
	}

	private function removeFromProcess(Player $player): void
	{
		$this->process = array_filter($this->process, fn(Player $p): bool => $p !== $player);
	}
}
