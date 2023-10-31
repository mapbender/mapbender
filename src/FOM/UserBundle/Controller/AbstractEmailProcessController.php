<?php


namespace FOM\UserBundle\Controller;


use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base class for controllers implementing user manipulation processes
 * involving emails and tokens.
 */
abstract class AbstractEmailProcessController extends UserControllerBase
{
    /** @var MailerInterface */
    protected $mailer;
    /** @var TranslatorInterface */
    protected $translator;

    protected $emailFromAddress;
    protected $emailFromName;
    protected $isDebug;

    public function __construct(MailerInterface $mailer,
                                TranslatorInterface $translator,
                                $userEntityClass,
                                $emailFromAddress,
                                $emailFromName,
                                $isDebug)
    {
        parent::__construct($userEntityClass);
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->emailFromAddress = $emailFromAddress;
        $this->emailFromName = $emailFromName ?: $emailFromAddress;
        $this->isDebug = $isDebug;
        if (!$this->emailFromAddress) {
            $this->debug404("Sender mail not configured. See UserBundle/CONFIGURATION.md");
        }
    }

    /**
     * Throws a 404, displaying the given $message only in debug mode
     *
     * @param string|null $message
     * @throws NotFoundHttpException
     */
    protected function debug404($message)
    {
        if ($this->isDebug && $message) {
            $message = $message . ' (this message is only display in debug mode)';
            throw new NotFoundHttpException($message);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param \DateTime $startTime
     * @param string $timeInterval
     * @return bool
     * @throws \Exception
     */
    protected function checkTimeInterval($startTime, $timeInterval)
    {
        $endTime = new \DateTime();
        $endTime->sub(new \DateInterval(sprintf("PT%dH", $timeInterval)));
        return !($startTime < $endTime);
    }

    protected function sendEmail($mailTo, $subject, $bodyText, $bodyHtml = null)
    {
        $message = new Email();
        $message->subject($subject);
        $message->from("$this->emailFromName <$this->emailFromAddress>");
        $message->to($mailTo);
        $message->text($bodyText);
        if ($bodyHtml) {
            $message->html($bodyHtml);
        }
        $this->mailer->send($message);
    }
}
