<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticActOnBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\Entity\Copy;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends CommonFormController
{
    /**
     * @param $idHash
     *
     * @return Response
     */
    public function previewAction($idHash)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');
        $copy  = $model->getCopyRepository()->findOneBy(['id' => $idHash]);

        if ($copy instanceof Copy && $copy->getId()) {
            $subject = $copy->getSubject();
            $content = $copy->getBody();
            // Convert emoji
            $content = EmojiHelper::toEmoji($content, 'short');
            $subject = EmojiHelper::toEmoji($subject, 'short');

            // Add subject as title
            if (!empty($subject)) {
                if (strpos($content, '<title></title>') !== false) {
                    $content = str_replace('<title></title>', "<title>$subject</title>", $content);
                } elseif (strpos($content, '<title>') === false) {
                    $content = str_replace('<head>', "<head>\n<title>$subject</title>", $content);
                }
            }

            return new Response($content);
        }

        return $this->notFound();

    }


}
