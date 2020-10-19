<?php namespace spitfire\io\cli;

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

class Stream
{
	
	private $stream;
	
	public function __construct($stream = STDOUT) {
		$this->stream = $stream;
	}
	
	/**
	 * 
	 * @param string $msg
	 * @return Stream
	 */
	public function out($msg) {
		fwrite($this->stream, $msg);
		return $this;
	}
	
	public function rewind() {
		return $this->out("\r" . exec('tput el'));
	}
	
	public function line() {
		return $this->out(PHP_EOL);
	}
	
	/**
	 * Some applications may behave differently depending on whether the current 
	 * output stream is a TTY (interactive console) or a pipe.
	 * 
	 * This is specially important for things like progress indicators, which in
	 * a TTY environment will add carriage returns to empty the current line, but
	 * when outputting to a file, this will result in wasted data being appended
	 * to the end of a file.
	 * 
	 * In the case of non-interactive progress indicators, these can just append
	 * symbols for every percent the complete and then append a success message
	 * when they're complete. This allows to tail the file with sensible output
	 * and does not clutter the logfile.
	 * 
	 * @return bool
	 */
	public function isInteractive() {
		return posix_isatty($this->stream);
	}
}
