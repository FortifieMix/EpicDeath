<?php

namespace Taskov1ch\EpicDeath\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class EpicDeathEvent extends Event implements Cancellable
{
	use CancellableTrait;

	public function __construct(
		private Player $player
	) {}

	public function getPlayer(): Player
	{
		return $this->player;
	}
}