<?php

namespace FortifiePE\EpicDeath;

use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;

class EventsListener implements Listener
{
	public function __construct(private Main $main) {}

	public function onDamage(EntityDamageEvent $event): void
	{
		if (!$event instanceof EntityDamageByEntityEvent) {
			return;
		}

		$victim = $event->getEntity();
		$damager = $event->getDamager();

		if ($this->isInProcess($victim) or $this->isInProcess($damager)) {
			$event->setCancelled(true);
			return;
		}

		if (!$victim instanceof Player or !$victim->hasPermission("epic.death.use") or $this->main->inCd($victim)) {
			return;
		}

		if ($victim->getHealth() > $event->getFinalDamage() or $victim === $damager) {
			return;
		}

		$event->setDamage(0);
		$this->main->startProcess($victim);
	}

	public function onBreak(BlockBreakEvent $event): void
	{
		$this->cancelIfInProcess($event->getPlayer(), $event);
	}

	public function onDrop(PlayerDropItemEvent $event): void
	{
		$this->cancelIfInProcess($event->getPlayer(), $event);
	}

	public function onInteract(PlayerInteractEvent $event): void
	{
		$this->cancelIfInProcess($event->getPlayer(), $event);
	}

	public function onCommand(PlayerCommandPreprocessEvent $event): void
	{
		$sender = $event->getPlayer();
		if ($sender instanceof Player) {
			$this->cancelIfInProcess($sender, $event);
		}
	}

	private function cancelIfInProcess(Player $player, $event): void
	{
		if ($this->main->inProcess($player)) {
			$event->cancel();
		}
	}

	private function isInProcess(Entity $entity): bool
	{
		return $entity instanceof Player && $this->main->inProcess($entity);
	}
}