# RSS Dispatcher

A comprehensive PHP-based RSS feed generator and playlist conversion utility designed for Radio Bahrain's content distribution system.

## Overview

RSS Dispatcher transforms various playlist formats into standardized RSS feeds, enabling seamless content syndication and automated broadcasting workflows. This tool serves as a critical component in modern radio station infrastructure, providing reliable, scalable RSS generation capabilities.

## Features

- **Multi-format Playlist Support**: Convert M3U, PLS, and custom playlist formats to RSS
- **Automated RSS Generation**: Transform audio playlists into XML/RSS feeds with proper metadata
- **Flexible Output Options**: Save generated feeds as `.xml` or `.rss` files
- **CLI Interface**: Command-line tools for batch processing and automation
- **Error Handling**: Comprehensive validation and error reporting
- **Extensible Architecture**: Modular design for easy customization and extension

## Quick Start

### Prerequisites
- PHP 7.4 or higher
- Composer for dependency management
- Web server (Apache/Nginx) for web interface

### Installation

```bash
# Clone the repository
git clone https://github.com/RadioBahrain/rss-dispatcher.git
cd rss-dispatcher

# Install dependencies
composer install

# Set appropriate permissions
chmod +x playlist-to-rss.php
```

### Basic Usage

```bash
# Convert a single playlist to RSS
php playlist-to-rss.php input.m3u output.rss

# Process multiple playlists
php playlist-to-rss.php --batch playlists/ feeds/

# Web interface
php -S localhost:8000 index.php
```

## Technical Architecture

The system employs a modular architecture with clear separation of concerns:

- **Core Functions** (`func.php`): RSS generation and playlist parsing logic
- **Web Interface** (`index.php`): Browser-based playlist management
- **CLI Tool** (`playlist-to-rss.php`): Command-line conversion utility
- **Configuration**: Environment-specific settings and API endpoints

## API Reference

### RSS Generation Functions

```php
// Generate RSS from playlist data
$rss = generateRSSFromPlaylist($playlistData, $options);

// Save RSS to file
saveRSSToFile($rss, 'output.rss');

// Validate RSS structure
$isValid = validateRSSFeed($rss);
```

## Contributing

We welcome contributions that enhance functionality, improve documentation, or extend platform compatibility. Please follow our coding standards and include comprehensive tests with any submissions.

## License

Licensed under the MIT License. See [LICENSE](LICENSE) for full terms and conditions.

## Support

For technical support or feature requests, please open an issue on our [GitHub repository](https://github.com/RadioBahrain/rss-dispatcher/issues).
