<?php namespace spitfire\exceptions\user;
/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use Exception;
use spitfire\exceptions\PublicExceptionInterface;
use spitfire\exceptions\ApplicationException as ExceptionsApplicationException;

/**
 * This kind of exception (or exceptions inheriting from it) are usually thrown when the
 * application runs into an issue that is neither a lack of permission or a resource that
 * can't be found.
 * 
 * Your application should not include sensitive information into the message of these
 * exceptions, the application will render this to the user.
 * 
 * In the event of your application wishing to provide additional information, use the
 * previous parameter to indicate the deeper rooted cause of the issue.
 */
class ApplicationException extends ExceptionsApplicationException implements PublicExceptionInterface
{
	
    public function httpCode() : int
    {
        return 500;
    }
}
