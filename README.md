MantisBT-Slack
==============

A [MantisBT](http://www.mantisbt.org/) plugin to send bug updates to [Slack](https://slack.com/), [Mattermost](https://about.mattermost.com/) and [Discord](https://discord.com/) channels.

# Setup
* The `master` branch requires Mantis 2.x, while the `master-1.2.x` branch works for Mantis 1.2.x.
* Extract this repo to your *Mantis folder/plugins/Slack*.
* On the Slack side:
  * Go to "More -> Automations -> Workflows -> New Workflow -> Build Workflow".
  * Start the workflow: "Starts with a webhook"
  * Add data variables as follows:
    * Key: user, Data type: Slack user ID
    * Key: text, Data type: Text
  * In the end, example HTTP body would look like this:
```json
{
  "text": "Example text",
  "user": "U123456789"
}
```
* On the MantisBT side:
  * As a manager:
    * Access the plugin's configuration page and fill in your Slack webhook URL.
  * As a user:
    * Access the account's configuration page and fill in your Slack user ID.

# Development
You can run a local development environment using Docker Compose:
- `docker-compose build && docker-compose up`
- Open http://localhost:8080/admin/install.php and install the database (using Admin credentials `root` / `root`)
- Login to Mantis using `administrator` / `root`
- Enable Slack plugin at http://localhost:8080/manage_plugin_page.php
