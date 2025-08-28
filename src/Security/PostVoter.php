<?php

namespace App\Security;

use App\Entity\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        // Admin a tous les droits sur l'article
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Sinon, seul l'auteur peut éditer/supprimer
        return $post->getAuthor() === $user;
    }
}


