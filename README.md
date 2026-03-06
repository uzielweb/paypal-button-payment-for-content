# PayPal Button for Joomla Content

A Joomla 6 extension that adds a PayPal payment button to your articles and modules.

## Features

- **Auto-insertion**: Automatically display payment buttons in specific categories.
- **Custom Fields Integration**: Pull product names and prices directly from Joomla Custom Fields.
- **Manual Tags**: Use `{paypal name="Product Name" price="29.99"}` anywhere in your content.
- **Customizable Design**: Choose between different button styles (Pill/Rectangle), colors (Gold, Blue, Silver, Black), and labels.
- **Sandbox Support**: Easy testing with PayPal Sandbox mode.

## Installation

1. Download the repository as a ZIP.
2. Go to Joomla Administrator > Extensions > Install.
3. Upload the ZIP file.
4. Go to Extensions > Plugins and enable "Content - PayPal Button".
5. Configure your PayPal email and other settings.

## Usage

### Auto-insertion
Ensure you have a custom field for the price (default: `price`). The plugin will look for this field and render the button based on the configuration.

### Manual Tag
Insert the following tag in your article or module:
`{paypal name="My Awesome Product" price="49.90"}`

## License
GNU General Public License version 2 or later.
