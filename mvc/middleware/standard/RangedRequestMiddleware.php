<?php namespace spitfire\mvc\middleware\standard;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\exceptions\user\ApplicationException;
use spitfire\io\stream\StreamSegment;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

/**
 * The ranged request middleware allows an application requesting data from our
 * application to indicate that they wish to only receive a part of the response.
 * 
 * This prevents unnecessary downloads when big file downloads get interrupted, or
 * when scrubbing through video in the client application.
 */
class RangedRequestMiddleware implements MiddlewareInterface
{
	
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		/**
		 * If the client sent the 'range' header, then we can proceed to trim the response
		 * to provide the segment that the application actually expected.
		 */
		$response = $handler->handle($request);
		$sent     = $request->getHeader('Range')[0];
		
		if ($sent)
		{
			/**
			 * If the request defines a range that is not in bytes, we will deny processing
			 * it.
			 */
			if (!\spitfire\utils\Strings::startsWith($sent, 'bytes=')) {
				throw new ApplicationException('Malformed range sent', 416);
			}
			
			/**
			 * If the application sent multiple ranges, we also ignore it, since it makes the
			 * processing unnecessarily complicated and the feature is rather obscure.
			 */
			if (strstr($sent, ',')) {
				throw new ApplicationException('Spitfire does not accept multiple ranges', 416);
			}
			
			/**
			 * Extract the byte range that the client wishes to read from our server.
			 */
			list($start, $end) = explode('-', substr($sent, 6));
			$stream = $response->getBody();
			
			$segment = new StreamSegment($stream, (int)$start, $end ?: min($stream->getSize() - 1, $start + 1.3 * 1024 * 1024));
			
			return $response->withStatus(206)
				->withHeader('Content-range', 'bytes ' . strval($segment->getStart()) . '-' . ($segment->getEnd()) . '/' . $stream->getSize())
				->withHeader('Content-length', (string)$segment->getSize())
				->withBody($segment);
		}
		
		return $response;
	}
}