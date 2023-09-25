This is a reimplementation of the BrowserQuest game server in PHP.
<br>More info on the original project can be found [here](https://github.com/mozilla/BrowserQuest).

It is still a WIP for the code structure, but the server works and accept players :) .

# Tech
- PHP 8.2
- Symfony 6.3
- Ratchet for the websocket server
- ReactPHP (included in Ratchet) for the timers

# Run the server
`php bin/console BrowserQuestServer`
