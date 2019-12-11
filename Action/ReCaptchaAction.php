<?php

namespace ReCaptcha\Action;

use ReCaptcha\Event\ReCaptchaCheckEvent;
use ReCaptcha\Event\ReCaptchaEvents;
use ReCaptcha\ReCaptcha;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class ReCaptchaAction implements EventSubscriberInterface
{
    /** @var  Request */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function checkCaptcha(ReCaptchaCheckEvent $event)
    {
        $requestUrl = "https://www.google.com/recaptcha/api/siteverify";

        $secretKey = ReCaptcha::getConfigValue('secret_key');
        $requestUrl .= "?secret=$secretKey";

        $captchaResponse = $event->getCaptchaResponse();

        if (null === $captchaResponse) {
            $captchaResponse = $this->request->request->get('g-recaptcha-response');
        }

        if (null !== $captchaResponse) {
            $requestUrl .= "&response=$captchaResponse";

            $remoteIp = $event->getRemoteIp();

            if (null === $remoteIp) {
                $remoteIp = $this->request->server->get('REMOTE_ADDR');
            }

            $requestUrl .= "&remoteip=$remoteIp";

            $result = json_decode(file_get_contents($requestUrl), true);

            if ((bool) $result['success'] === true) {
                $event->setHuman(true);
            }
        } else {
            // No captcha in form -> consider user as human.
            $event->setHuman(true);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ReCaptchaEvents::CHECK_CAPTCHA_EVENT => ['checkCaptcha', 128],
        ];
    }
}
