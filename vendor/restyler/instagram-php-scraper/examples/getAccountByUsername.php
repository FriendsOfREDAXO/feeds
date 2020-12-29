<?php
require __DIR__ . '/../vendor/autoload.php';

// If account is public you can query Instagram without auth

$instagram = new \InstagramScraper\Instagram();

// set RapidApi key to use https://rapidapi.com/restyler/api/instagram40
$instagram->setRapidApiKey('YOUR-RAPID-API-KEY');

// For getting information about account you don't need to auth:
$account = $instagram->getAccountInfo('kevin');

// Available fields
echo "Account info:\n";
echo "Id: {$account->getId()}\n";
echo "Username: {$account->getUsername()}\n";
echo "Full name: {$account->getFullName()}\n";
echo "Biography: {$account->getBiography()}\n";
echo "Profile picture url: {$account->getProfilePicUrl()}\n";
echo "External link: {$account->getExternalUrl()}\n";
echo "Number of published posts: {$account->getMediaCount()}\n";
echo "Number of followers: {$account->getFollowsCount()}\n";
echo "Number of follows: {$account->getFollowedByCount()}\n";
echo "Is private: {$account->isPrivate()}\n";
echo "Is verified: {$account->isVerified()}\n";
