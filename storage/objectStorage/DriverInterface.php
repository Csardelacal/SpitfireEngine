<?php namespace spitfire\storage\objectStorage;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * This class determines what behaviors a blob storage needs to define to be 
 * compatible with Spitfire.
 * 
 * Your driver may not support all of the operations Spitfire provides to applications,
 * in this case you can always throw an IOXException, or even a more specific one
 * to let users know that the operation is unsupported.
 * 
 * Please note that missing features in your driver may potentially damage or alter
 * the applications relying on them. For example, not implementing the expires method
 * on your driver is unlikely to result in a broken application, on the other hand,
 * not implementing write may result in your application not working at all.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
interface DriverInterface
{
	
	/**
	 * 
	 * NOTE: The driver is not made aware of the scheme it's providing. It will 
	 * only serve the data. If your driver needs access to the scheme, it's likely
	 * that it's not working the right way
	 * 
	 * @param string $dsn Configuration string for the driver
	 */
	public function __construct($dsn);
	
	/**
	 * Reads the entire blob from the source device / service / network. This is a
	 * very convenient method for handling smaller blobs like text files or memcached
	 * keys.
	 * 
	 * This is likely to have your server run out of memory if you're managing big
	 * files, for that you should refer to the stream() method.
	 * 
	 * This method must be implemented in a synchronous manner. It does not support
	 * asynchronous calls.
	 * 
	 * @param string $key
	 * @throws IOException If the blob cannot be retrieved
	 */
	public function read($key);
	
	/**
	 * Writes a blob to the collection. Please note that this method requires a
	 * Blob as it's second parameter, you can easily create a blob using the blob()
	 * function with a mime type and a string.
	 * 
	 * @param string $key
	 * @param Blob $contents
	 * @param int $ttl Time in seconds until the key expires and the blob should be freed
	 */
	public function write($key, $contents, $ttl = null);
	
	/**
	 * Allows the driver to indicate that a certain key is read only and therefore
	 * not writable.
	 * 
	 * @param string $key
	 */
	public function readonly($key);
	
	/**
	 * Returns whether the collection contains an unexpired entry for the key provided.
	 * 
	 * @throws IOException If the collection is not readable
	 * @param string $key
	 */
	public function contains($key);
	
	/**
	 * Returns the mime content-type for the file provided. The system may use different
	 * mechanisms for detecting the content/type or may be returning an improper one.
	 * 
	 * Do not use this to replace validating the contents of a file. A file reported
	 * as, for example, jpeg by the storage driver may actually be a PDF to a client's computer.
	 * 
	 * @param string $key
	 */
	public function mime($key);
	
	/**
	 * Returns the length in bytes of the blob. This is useful when performing seek
	 * operations, checking whether a drive that we wish to write to has enough
	 * capacity to receive this bob or checking whether the blob satisfies certain 
	 * conditions to be transmitted over the network.
	 * 
	 * While in my original vision, the length was connected to whether the system
	 * could stream the blob, it seems that certain mechanisms that do not support
	 * streaming should be able to report the size of the blob they hold.
	 * 
	 * @todo Some blobs may not have not have a determined length in which case this should thrown an exception
	 * @param string $key
	 */
	public function length($key);
	
	/**
	 * Returns the unix timestamp the blob was modified at. 
	 * 
	 * @param string $key
	 */
	public function mtime($key);
	
	/**
	 * Returns the las access time of the blob. Please note that this may be affected
	 * by third party applications scanning your server's directories.
	 * 
	 * @param type $key
	 */
	public function atime($key);
	
	/**
	 * Removes the blob from the server. Drivers are allowed to perform advisory
	 * deletions, meaning that the data is not removed from the media it resides on 
	 * immediately but marked as deleted instead.
	 * 
	 * @param type $key
	 */
	public function delete($key);
	
	/**
	 * Returns a public URL for the file. This allows users to download the file
	 * from a different server (eventually for a restricted amount of time)
	 * 
	 * This is extremely helpful in scenarios where the storage is not on the websever
	 * but on something like Cloudy, S3 or Digital Ocean. You can just have the 
	 * server generate a link that the user can use to download the file.
	 * 
	 * The link may be generated for a single use, or just as many as the user wishes.
	 * Your server does not need to host the file.
	 * 
	 * @param string $key
	 * @param int $ttl
	 */
	public function url($key, $ttl);
	
	/**
	 * Allows the application to start streaming to the storage engine. This greatly
	 * increases the responsiveness of the application since it will receive control
	 * back in regular intervals.
	 * 
	 * This can ensure that a long file transfer does not prevent the system from
	 * attending other tasks, and the application can still inform the user about 
	 * progress.
	 * 
	 * @param string $key
	 * @return \spitfire\storage\objectStorage\IOStream
	 */
	public function stream($key): IOStream;
}
