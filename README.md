CryptoStats
===========

CryptoStats it's a cryptocurrency exchange aggregator for local use.

1. Installation

Before install you need to install [docker-compose](https://docs.docker.com/compose/install/).

Step 1: Download code
<pre>
git clone git@github.com:Yurujai/CryptoStats.git CryptoStats
</pre>

Step 2: Go to the downloaded folder
<pre>
cd CryptoStats
</pre>

Step 3: Build docker
<pre>
make build
</pre>

Step 4: Start containers
<pre>
make start
</pre>

Step 5: Install dependencies
<pre>
make install
</pre>

Step 6: Clear cache
<pre>
make cache
</pre>

If you have an error after clear cache do the next lines:

<pre>
docker exec -it php-fpm sh
chown -R www-data:www-data var
</pre>

2. Configuration

This project have .env file where you can modify some variables to set your API Keys.

IMPORTANT: Don't share your API Keys. It's most secure activate API for exchange on read mode. The software don't need more permission than read.

Step 1: Edit file .env

Step 2: Modify exchanges API keys.

```
### Exchanges APIs
BINANCE_ENABLED=false
BINANCE_API_KEY={yourBinanceAPIKey}
BINANCE_API_SECRET={yourBinanceAPISecret}

BITVAVO_ENABLED=false
BITVAVO_API_KEY={yourBitvavoAPIKey}
BITVAVO_API_SECRET={yourBitvavoAPISecret}

KUKOIN_ENABLED=false
KUKOIN_API_KEY={yourKukoinAPIKey}
KUKOIN_API_SECRET={yourKukoinAPISecret}
```

Step 3: Activate exchanges

To download and show your balances for an exchange you need to enable the exchange on the .env file
```
### Exchanges APIs
BINANCE_ENABLED=true
```

3. How to access

To access the software execute the following lines on the terminal
<pre>
docker inspect php-webserver
</pre>

The output of this command will be like this
```
...
"Gateway": "xxx.xxx.xxx.xxx",
"IPAddress": "xxx.xxx.xxx.xxx",
"IPPrefixLen": xx,
...
```

Get the IP of the key IPAddress and put it on your web browser.

Enjoy!
