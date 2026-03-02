<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\CommentUserConnector\Business\Validator;

use Generated\Shared\Transfer\CommentRequestTransfer;
use Generated\Shared\Transfer\CommentValidationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Zed\CommentUserConnector\Business\Reader\UserReaderInterface;
use Spryker\Zed\CommentUserConnector\Persistence\CommentUserConnectorRepositoryInterface;

class CommentValidator implements CommentValidatorInterface
{
    /**
     * @var string
     */
    protected const GLOSSARY_KEY_COMMENT_ACCESS_DENIED = 'comment.validation.error.access_denied';

    /**
     * @var string
     */
    protected const GLOSSARY_KEY_USER_NOT_FOUND = 'comment.validation.error.user_not_found';

    /**
     * @var string
     */
    protected const GLOSSARY_KEY_AUTHOR_INTERSECTED = 'comment.validation.error.comment_author_intersected';

    /**
     * @var \Spryker\Zed\CommentUserConnector\Business\Reader\UserReaderInterface
     */
    protected UserReaderInterface $userReader;

    /**
     * @var \Spryker\Zed\CommentUserConnector\Persistence\CommentUserConnectorRepositoryInterface
     */
    protected CommentUserConnectorRepositoryInterface $commentUserConnectorRepository;

    public function __construct(
        UserReaderInterface $userReader,
        CommentUserConnectorRepositoryInterface $commentUserConnectorRepository
    ) {
        $this->userReader = $userReader;
        $this->commentUserConnectorRepository = $commentUserConnectorRepository;
    }

    public function validateCommentAuthor(CommentRequestTransfer $commentRequestTransfer): CommentValidationResponseTransfer
    {
        $commentValidationResponseTransfer = (new CommentValidationResponseTransfer())->setIsSuccessful(false);

        if ($this->isCommentAuthorIntersected($commentRequestTransfer)) {
            return $commentValidationResponseTransfer->addMessage(
                $this->createMessageTransfer(static::GLOSSARY_KEY_AUTHOR_INTERSECTED),
            );
        }

        $idUser = $commentRequestTransfer->getCommentOrFail()->getFkUserOrFail();
        $userTransfer = $this->userReader->findUserById($idUser);
        if (!$userTransfer) {
            return $commentValidationResponseTransfer->addMessage(
                $this->createMessageTransfer(static::GLOSSARY_KEY_USER_NOT_FOUND),
            );
        }

        $idComment = $commentRequestTransfer->getCommentOrFail()->getIdComment();
        if ($idComment && !$this->commentUserConnectorRepository->isUserCommentAuthor($idUser, $idComment)) {
            return $commentValidationResponseTransfer->addMessage(
                $this->createMessageTransfer(static::GLOSSARY_KEY_COMMENT_ACCESS_DENIED),
            );
        }

        return $commentValidationResponseTransfer->setIsSuccessful(true);
    }

    protected function isCommentAuthorIntersected(CommentRequestTransfer $commentRequestTransfer): bool
    {
        $commentTransfer = $commentRequestTransfer->getCommentOrFail();

        return $commentTransfer->getCustomer() && $commentTransfer->getCustomerOrFail()->getIdCustomer();
    }

    protected function createMessageTransfer(string $message): MessageTransfer
    {
        return (new MessageTransfer())->setValue($message);
    }
}
