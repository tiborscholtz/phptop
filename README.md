# PHPTop

PHPTop is a lightweight PHP-based web application that provides an easy-to-use interface for monitoring system performance. It processes and displays the output of the Unix `top` command in a more readable and customizable format, making it ideal for administrators and developers who want a quick overview of their system's resource usage.

## Features

- **Real-time system monitoring**: View CPU, memory, and process information.
- **Lightweight and fast**: Built using PHP, ensuring minimal system resource usage.
- **Simple installation**: No need for complex configuration or additional software dependencies.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [License](#license)

## Installation

### Prerequisites

Before installing PHPTop, make sure you have the following installed:

- **PHP** (7.3 or higher)
- **Apache/Nginx** or any other PHP-compatible web server (optional for local testing)
- **Unix-based OS** (Linux, macOS) or WSL (for Windows)

### Step-by-Step Installation

1. Clone the repository:

    ```bash
    git clone https://github.com/tiborscholtz/phptop
    ```

2. Navigate to the project directory:

    ```bash
    cd phptop
    ```

3. Configure your web server to point to the PHPTop directory (if you're using Apache or Nginx).

4. Make sure `top` command is available on your system (most Unix-based systems have it pre-installed).

5. Open `index.php` in your browser:

    ```
    http://localhost/phptop
    ```

That's it! PHPTop should now be running on your web server.

## Usage

Once PHPTop is installed, simply navigate to the directory containing the `index.php` file in your browser to start monitoring your system’s performance. The application will automatically fetch the output of the `top` command and display it in a user-friendly interface.

### Commands & Options

Coming soon!

## Configuration

Coming soon!

## Contributing

We welcome contributions to PHPTop! If you'd like to contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch for your changes.
3. Make your changes and write tests (if necessary).
4. Submit a pull request.

Before contributing, please ensure that your changes adhere to the existing code style and pass all tests.

### Reporting Issues

If you encounter any bugs or issues, please open an issue on the GitHub repository. Include detailed information about the problem, including:

- Your operating system version
- PHP version
- Steps to reproduce the issue
- Any error messages or logs

## License

PHPTop is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

---

### Contact

- **Author**: [tiborscholtz](https://github.com/tiborscholtz)
- **GitHub**: [https://github.com/tiborscholtz/phptop](https://github.com/tiborscholtz/phptop)

---