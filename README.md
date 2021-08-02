## Telgraf

Simple live support server with PHP Swoole Websocket and Telegram API.

### Usage

#### Server Setup

1. Clone repository with following command.
   ```
   git clone https://github.com/ademalidurmus/telgraf.git
   ```
2. Enter project folder with `cd telgraf` command.
3. Update initial credentials (_BOT_TOKEN_, _APP_SECRET_ and _APP_CHAT_IDS_) from `.env.example` file via any text editor.
4. You can use some makefile commands for configurations. Makefile commands like **make [COMMAND]**, for details you can run `make help`.
   1. Use `make env` command for create environment file.
      > You need update initial credentials from `.env.example` file before using makefile commands. If you were run `make .env` command, you may need update `.env` and `.env.example` files at the same times.
   2. Run `make build` for building telgraf application. This command also run `composer install` for dependency installation and serves the application.
   3. If you are already built app, and you need just serve app you can run `make up`. For restart app server you can run `make restart`, for stop app server you can also run `make stop`.
   4. For access to cli or container bash you can run `make cli`.
   5. `make status` command is shows containers status like `docker ps`. If you want to show telgraf logs you can use `make logs` command.
   6. `make set_webhook` command setting telegram bot webhook using defined environments.
   7. `make delete_webhook` command delete telegram bot webhook using defined environments.
   8. `make clean` command stops telgraf server, delete `.env` file and clear all log files.

#### Telegram Bot Commands

- `/start` command is for the starting agent session to accept any client connections.
- `/stop` command is for the stopping agent session.
- `/close` command is for the stopping current client connection. The agent will continue to wait for any connection.
- `/add [CHAT_ID]` command is used to add a new agent to the agent access control list.
- `/remove [CHAT_ID]` command for removing the agent from the agent access control list.

#### API Docs

`wss://{your_webserver_url}`

- `type`: _enum(message|info)_, action type
- `content`: _string_, message text or action details
- `attributes`: _object_, message attributes
  - `attributes.name`: _string_, client name

Sample webscoket message history:

```
❌ Disconnected from wss://telgraf.durmus.me
⬇️ {"type":"info","content":"connection unassigned","attributes":[]}
⬆️ {"type":"message","content":"Test Client Message 2","attributes":{"name":"Client 1"}}
⬇️ {"type":"message","content":"Test Message 2","attributes":{"name":"Adem Ali D."}}
⬆️ {"type":"message","content":"Test Client Message 1","attributes":{"name":"Client 1"}}
⬇️ {"type":"message","content":"Test Message 1","attributes":{"name":"Adem Ali D."}}
⬇️ {"type":"info","content":"connection assigned","attributes":[]}
✔️ Connected to wss://telgraf.durmus.me
```

### License

MIT
