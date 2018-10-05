<?php

namespace OCA\SocialLogin\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

class CustomOAuth2 extends OAuth2
{

    /**
     * @return User\Profile
     * @throws UnexpectedApiResponseException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    public function getUserProfile()
    {
        $profileFields = array_filter(
            array_map('trim', explode(',', $this->config->get('profile_fields'))),
            function ($val) { return !empty($val); }
        );
        $profileUrl = $this->config->get('endpoints')->get('profile_url');

        if (count($profileFields) > 0) {
            $profileUrl .= (strpos($profileUrl, '?') !== false ? '&' : '?') . 'fields=' . implode(',', $profileFields);
        }

        $response = $this->apiRequest($profileUrl);
        if (!isset($response->identifier) && isset($response->id)) {
            $response->identifier = $response->id;
        }
        if (!isset($response->identifier) && isset($response->data->id)) {
            $response->identifier = $response->data->id;
        }

        $data = new Data\Collection($response);

        if (! $data->exists('identifier')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        foreach ($data->toArray() as $key => $value) {
            if (property_exists($userProfile, $key)) {
                $userProfile->$key = $value;
            }
        }
        if (!empty($userProfile->email)) {
            $userProfile->emailVerified = $userProfile->email;
        }

        return $userProfile;
    }
}
