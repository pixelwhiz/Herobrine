# Herobrine Plugin for PocketMine-MP

![Plugin Icon](assets/icon.png)

A fully-featured Herobrine implementation for PocketMine-MP servers with custom behaviors, rewards system, and tracking features.

## Table of Contents

- [Requirements](#requirements)
- [Features](#features)
- [Installation](#installation)
- [Commands](#commands)
- [API Integration](#api-integration)
- [Preview](#preview)
- [Contributing](#contributing)
- [License](#license)

## Requirements <a name="requirements"></a>

This plugin requires the following dependencies:

- [ResinAPI Plugin](https://github.com/pixelwhiz/ResinAPI) - Economy/Payment system
- [InvMenu Virion](https://github.com/Muqsit/InvMenu) - For reward inventory management
- PocketMine-MP 5.0.0 or higher
- PHP 8.0 or higher
## Features <a name="features"></a>

- **Authentic Herobrine Entity** with multiple behavior phases
- **Custom AI Behaviors** including teleportation, attacks, and environmental interactions
- **Rewards System** with configurable loot tables
- **Multi-world Support** with position tracking
- **Boss Bar Integration** showing health and status
- **Weather Effects** synchronized with Herobrine's presence
- **Admin Commands** for complete control
- **Custom Skins & Models** for immersive experience

## Installation <a name="installation"></a>

1. Download the latest release from [releases page](https://github.com/pixelwhiz/Herobrine/releases)
2. Download [requirement](#requirements) files
3. Place the `Herobrine.phar` and requirement files in your server's `plugins` folder
4. Restart your server
5. Done

## Commands <a name="commands"></a>

| Command      | Description                                   | Permission                     | Default |
|--------------|-----------------------------------------------|--------------------------------|---------|
| `/hb help`   | Shows all available commands                  | `herobrine.command.help`       | `true`  |
| `/hb spawn`  | Spawns Herobrine at your location             | `herobrine.command.spawn`      | `op`    |
| `/hb pos`    | Shows Herobrine's current/last position       | `herobrine.command.position`   | `true`  |
| `/hb tp`     | Teleports you to Herobrine                    | `herobrine.command.teleport`   | `op`    |
| `/hb tphere` | Teleports Herobrine to you                    | `herobrine.command.tphere`     | `op`    |
| `/hb kill`   | Removes Herobrine from your world             | `herobrine.command.kill`       | `op`    |
| `/hb rewards`| Set and configure rewards                     | `herobrine.command.rewards`    | `op`    |


## API Integration <a name="api-integration"></a>

```php
// Get Herobrine instance
$herobrine = \pixelwhiz\herobrine\Herobrine::getInstance();

// Check if Herobrine exists in world
if ($herobrine->isEntityExists($world)) {
    // Do something
}

// Get Herobrine entity in world
$entity = $herobrine->getEntityByWorld($world);
```

## Preview <a name="preview"></a>

[![Herobrine Plugin Preview](https://img.youtube.com/vi/MESuhpozCww/0.jpg)](https://www.youtube.com/watch?v=MESuhpozCww)

Watch full preview at [YouTube](https://www.youtube.com/watch?v=MESuhpozCww)

## Contributing <a name="contributing"></a>

All kinds of contribution are welcome
- Send feedbacks.
- Submit bug reports.
- Write / Edit the documents.
- Fix bugs or add new features.

and if you found bug or have any issues please report them [here](https://github.com/pixelwhiz/Herobrine/issues/new)

## License <a name="license"></a>

This project is licensed under LGPL-3.0. Please see [LICENSE](LICENSE) file for details.
