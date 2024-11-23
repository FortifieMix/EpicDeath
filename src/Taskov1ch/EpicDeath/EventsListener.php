<?php

namespace Taskov1ch\EpicDeath;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use Taskov1ch\EpicDeath\events\EpicDeathEvent;

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
			$event->cancel();
			return;
		}

		if (!$victim instanceof Player or !$victim->hasPermission("epic.death.use") or $this->main->inCd($victim)) {
			return;
		}

		if ($victim->getHealth() > $event->getFinalDamage() or $victim === $damager) {
			return;
		}

		$customEvent = new EpicDeathEvent($victim);
		$customEvent->call();

		if ($customEvent->isCancelled()) {
			return;
		}

		$event->setBaseDamage(0);
		$this->main->startProcess($victim);
	}

	public function onDrop(PlayerDropItemEvent $event): void
	{
		$this->cancelIfInProcess($event->getPlayer(), $event);
	}

	public function onInteract(PlayerInteractEvent $event): void
	{
		$this->cancelIfInProcess($event->getPlayer(), $event);
	}

	public function onCommand(CommandEvent $event): void
	{
		$sender = $event->getSender();
		if ($sender instanceof Player) {
			$this->cancelIfInProcess($sender, $event);
		}
	}

	public function onMove(PlayerMoveEvent $event): void
	{
		$player = $event->getPlayer();
		$distance = $event->getFrom()->distanceSquared($event->getTo());

		if ($this->main->inProcess($player) && $distance >= 0.01) {
			$event->cancel();
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