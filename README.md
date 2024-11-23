<div align="center">

<img src="dont_touch_me/512x512.png" width="130rem">

# EpicDeath 🗡
This plugin allows players with the `epic.death.use` permission to leave the battlefield in style by soaring into the skies. Sound settings, flight duration, and cooldown can be customized in the configuration file.

</div>

## Features ✨
- Grants players with the `epic.death.use` permission a unique and dramatic way to exit by flying into the skies upon death.
- Fully customizable settings in the configuration file, including:
  - **Sound effects**: Choose the sounds played during the flight.
  - **Flight duration**: Adjust how long the player ascends.
  - **Cooldown**: Set a cooldown to balance gameplay.
- Simple and lightweight.
- Does not interfere with the **PlayerDeathEvent** event, ensuring compatibility with other plugins or events.

## For Developers 🛠
- There is an event **[EpicDeathEvent](src/Taskov1ch/EpicDeath/events/EpicDeathEvent.php)** that allows you to prevent the dramatic death effect if needed.
- Permission for the dramatic death effect: `epic.death.use`.