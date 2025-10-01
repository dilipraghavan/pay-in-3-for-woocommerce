Pay-In-3 for WooCommerce Gateway
=================================

A custom-built payment gateway plugin for WooCommerce that enables store owners to offer a **"Pay in 3 Installments"** payment option at checkout. The solution is designed to increase conversions by making high-value products more accessible while utilizing best-practice WooCommerce integration techniques.

* * * * *

✨ Features
----------

-   **Custom Gateway Integration:** Registers and manages a complete, native payment method within the WooCommerce checkout flow.

-   **Dynamic UI Control:** Utilizes the specific dynamic hook (`woocommerce_thankyou_{gateway_id}`) to completely control and customize the "Order Received" (Thank You) page content.

-   **Streamlined Confirmation:** Intentionally suppresses default WooCommerce output (like the product table and addresses) to provide a **focused, custom message** that prioritizes the installment payment terms.

-   **Installment Scheduling:** Simulates the secure logging of a future 3-part installment schedule (30 and 60 days) to the database, ready for a separate billing process.

-   **Secure Coding Standards:** Adheres to WordPress security best practices, including mandatory unslashing (`wp_unslash()`) before sanitization of input data.

-   **Auditable Logging:** Logs the initial payment and the installment setup process directly into the standard WooCommerce **Order Notes** for an immediate, auditable trail.

* * * * *

🛠 Installation
---------------

### For End-Users (Packaged Plugin)

To install a ready-to-use version of the plugin, download the latest release from the official releases page.

1.  Download the **.zip** file from the latest release: **[Click here to download the latest release](https://github.com/dilipraghavan/pay-in-3-for-woocommerce/releases)**.

2.  In the WordPress dashboard, go to **Plugins** → **Add New**.

3.  Click **Upload Plugin**, select the downloaded **.zip** file, and click **Install Now**.

4.  After installation, click **Activate Plugin**.

### For Developers (Standard Git)

This is the recommended method for developers to work with the source code.

1.  **Clone the Repository:** Clone the plugin from GitHub to your local machine using Git.

    Bash

    ```
    git clone https://github.com/dilipraghavan/pay-in-3-for-woocommerce.git wp-content/plugins/pay-in-3-wc

    ```

2.  **Activate Plugin:** Activate the plugin from the WordPress Plugins screen.

* * * * *

⚙️ Configuration
----------------

The gateway is configured using the standard WooCommerce interface.

### Step 1: Enable the Gateway

1.  Navigate to **WooCommerce** → **Settings** → **Payments**.

2.  Find the **"Pay-In-3 Installments"** gateway and click **Manage** or ensure it is enabled.

### Step 2: Configure Details

1.  Customize the **Title** and **Description** that will be shown to customers at checkout.

2.  (Optional) Define any minimum or maximum order amounts for which the gateway should be available.

3.  Save your changes.

* * * * *

🚀 Usage
--------

The gateway automatically appears on the checkout page when the following conditions are met:

1.  The payment method is enabled in the WooCommerce settings.

2.  The cart total meets any minimum/maximum requirements set in the configuration.

### Custom Confirmation Output

The core value is displayed on the **Order Received Page**. After a successful order:

-   The standard WooCommerce product table and addresses are suppressed.

-   A custom message, **"Payment Plan Details"**, appears, confirming the first payment and detailing the remaining two installment dates.

* * * * *

📊 Viewing Reports
------------------

To view the technical logs related to the payment and installment setup, inspect the **Order Notes** on the individual order detail page in the WordPress admin:

1.  Navigate to **WooCommerce** → **Pay in 3 Logs**.

2.  Select the gateway log file.


* * * * *

🤝 Contributing
---------------

Contributions are welcome! If you find a bug or have a suggestion, please open an issue or submit a pull request on the GitHub repository.

**GitHub Repository:** <https://github.com/dilipraghavan/pay-in-3-for-woocommerce.git>

License
--------

This project is licensed under the MIT License. See the **[LICENSE file](https://github.com/dilipraghavan/pay-in-3-for-woocommerce/blob/main/LICENSE)** for details.
