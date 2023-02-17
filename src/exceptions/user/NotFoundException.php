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


use spitfire\exceptions\NotFoundException as ExceptionsNotFoundException;
use spitfire\exceptions\PublicExceptionInterface;

/**
 * Whenever this exception, or a exception that inherits from it is raised, the user is
 * trying to access a resource that is not available to the system, or to the user.
 */
class NotFoundException extends ExceptionsNotFoundException implements PublicExceptionInterface
{

	public function httpCode() : int
	{
        return 404;
    }
}
