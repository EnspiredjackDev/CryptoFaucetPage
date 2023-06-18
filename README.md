# CryptoFaucetPage
## Info
##### Simple PHP site that gives a small amount of crypto to a user. 
- Detects if a user has already used the faucet in the last 24 hours (by default)
- Can change the output of the faucet based on country (for things like cpm rate differences) - ~~badly done, may change in future~~ Now uses coingecko API for pricing data
- Has reCAPTCHA V2 to stop bots and automated traffic
- Very simple layout
#### Potential issues:
- `http://ip-api.com/` which is used to check the user's country, only allows 45 requests per minute per ip.
- `https://api.coingecko.com/api/v3/simple/` which is used to check the price of the coin, only allows [10-30] (https://www.coingecko.com/en/api/pricing)requests per minute per ip.  
## Requirements  
- Node of cryptocurrency with RPC open  
- reCAPTCHA v2 API keys. Get them [here.](https://www.google.com/recaptcha/about/)  
- SQL Database  
- URL Shortener (optional)

## Node setup  
1. Navigate to the default data directory, `C:\Users\username\AppData\Roaming\Coinname\` by default on windows or `/home/username/.coinname/` on linux.  
2. create a file called `coinname.conf` in the directory.  
3. make sure it includes: 
```
rpcuser=username
rpcpassword=password
rpcport=port
rpcallowip=127.0.0.1
daemon=1
server=1
listen=1
```  
4. Done, put the credentials and port into the php script.  

## SQL setup  
#### This is just for setup of what the faucet expects the table to look like, not a full installation guide.

1. Create a new schema. `CREATE SCHEMA your_schema_name;`
2. Create the table with these specific columns:
```
CREATE TABLE your_schema_name.your_table_name (
    ip VARCHAR(45),
    timestamp TIMESTAMP,
    unique_id VARCHAR(255),
    crypto_address VARCHAR(255),
    country VARCHAR(50) NOT NULL,
    used INT NOT NULL DEFAULT '0'
);

```
3. Done, put the credentials, port, schema and table name into the php script.

## URL Shortener setup (optional)
### If you don't want the shortlink feature then please read the note on line 113
- Any Shortener is supported if the syntax is similar to this:`?api={$api_token}&url={$long_url}&format=text` for plaintext response.  
- Get the site location from your webserver, eg, `https://https://example.com/faucet/` or `https://example.com/faucet/coin.php` and set it in the script.  
- Be sure to set the shortlink service API key.

## Other setup and config 
#### Coingecko API ids for coins
A coin's ID can be found on the coin page for example, [bitcoin's](https://www.coingecko.com/en/coins/bitcoin) would be `bitcoin`, you can find it on the right side of the page labelled `API ID`  
#### The first letters of an address
To try to prevent users from using the wrong address, on line 62 there is a check to make sure that the address starts with the correct letters, currently it is set to litecoin's, change it to whatever your currency has.
#### Changing the time between uses
On line 56, `NOW() - INTERVAL 24 HOUR")` can be changed to any number, but to make it more readable use:     `NOW() - INTERVAL 24 MINUTE")` for minutes or `NOW() - INTERVAL 24 DAY")` for days.
#### Changing the payment amount per country
Lines 134-200 are setup to deal with country by country payments, the numbers are setup like: `CPM / 1000 / pricePerCoin` where cpm is how much the shortlink service pays you, 1000 stays the same and pricePerCoin is at what sell rate you want to sell the coin to users.
#### Space for ad
Lines 338-340 is space for an iframe ad with some margin away from the submit box.
#### Footer
Feel free to change the footer text, I just wanted it to look a bit professional but please leave the html comment under it.
