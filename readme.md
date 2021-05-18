# OTP

## Demo

- Demo is available at https://anvalchev.live/signup/
- note already registered user will not be able to register again or verify if they exit
- only one account per email is allowed, but one phone number can be used with multiple accounts

## Run locally

- run `npm install` to install dependencies

## Ways to complete/ improve

- handle subsequent verification code generation after 3 failed attemps - either automatically, or by allowing the user to do it manually and not expiring the last verification code
- verification code expiration
- restructure/ separate logic using different classes/ namespaces
- handle prettry URLs and URL parametars eg. /rest/v1/users/...
- handle security better - sanitize input data and return errors of input
- move attempts counter to the backend as well, checking how many attempts have been made and returning a 1 minute (or the remainer of) counter to the front end (even on refresh return the remaining time only)
- handle subsequent registration events, allowing a user to verity their phone number at a later date (direct link to verification screen)
- check if email already exists while user is typing
- allow one phone to verify multiple accounts (if needed) - verify not only phone number and code but email as well
- allow different verification code variations (maybe also letters or shorter/ longer code?)
- integrate SMS gateway and check if SMS was sent or not, returning an error/ notification to the user if it was not