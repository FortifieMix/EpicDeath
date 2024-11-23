<?php

namespace Taskov1ch\EpicDeath;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\particle\MobSpawnParticle;

class Main extends PluginBase
{
	private array $process = [];
	private array $cd = [];
	private array $config;

	protected function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventsListener($this), $this);
		$this->config = $this->getConfig()->getAll();
	}

	protected function onDisable(): void
	{
		array_map(fn(Player $player): void => $player->kill(), $this->process);
		$this->process = [];
	}

	private function sendSound(array $sounds, Player $player): void
	{
		$pos = $player->getPosition();
		$worldPlayers = $player->getWorld()->getPlayers();

		array_map(function ($sound) use ($pos, $worldPlayers): void {
			$pk = PlaySoundPacket::create(
				$sound, $pos->getX(), $pos->getY(), $pos->getZ(),
				$this->config["volume"], $this->config["pitch"]
			);
			array_map(fn(Player $p): bool => $p->getNetworkSession()->sendDataPacket(clone $pk), $worldPlayers);
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
		$tmpGm = $player->getGamemode();
		$player->setGamemode(GameMode::ADVENTURE());
		$this->sendSound($this->config["start"], $player);

		$player->getEffects()->add(new EffectInstance(
			VanillaEffects::LEVITATION(), $this->config["duration"] * 20 + 10, 0, false
		));

		$task = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
			function () use ($player): void
			{
				$pos = $player->getPosition();
				$world = $player->getWorld();

				for ($i = 0; $i < 10; $i++) {
					$world->addParticle(
						$pos->add(mt_rand(-5, 5) / 10, mt_rand(-5, 5) / 10, mt_rand(-5, 5) / 10),
						new EnchantmentTableParticle()
					);
				}
			}
		), 10);

		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
			function () use ($player, $task, $tmpGm): void {
				$task->remove();
				$this->endProcess($player, $tmpGm);
			}
		), $this->config["duration"] * 20);
	}

	private function endProcess(Player $player, GameMode $tmpGm): void
	{
		$player->setGamemode($tmpGm);
		$this->sendSound($this->config["end"], $player);

		for ($i = 0; $i < 10; $i++) {
			$player->getWorld()->addParticle($player->getPosition(), new MobSpawnParticle(3, 3));
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