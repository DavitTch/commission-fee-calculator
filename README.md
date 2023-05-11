## How to run project

- composer install.
- copy .env.example to .env and set the following variable values in .env
  - EXCHANGE_RATE_URL, DEPOSIT_FEE, 
  - PRIVATE_WITHDRAW_RATE,
  - BUSINESS_WITHDRAW_RATE,
  - PRIVATE_WITHDRAW_FREE_LIMIT,
  - PRIVATE_WITHDRAW_FREE_LIMIT_COUNT
  
- There is sample file in storage folder named input-example.csv. You can modify it or add another file and use file path in command: php artisan calculate:fee {filePath} for sample file you can run php artisan calculate:fee input-example.csv
- If you would like to test application run following command: php artisan test
