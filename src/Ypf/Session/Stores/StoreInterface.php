<?php
namespace Ypf\Session\Stores;

interface StoreInterface
{
	/**
	 * Writes session data.
	 *
	 * @access  public
	 * @param   string  $sessionId    Session id
	 * @param   array   $sessionData  Session data
	 * @param   int     $dataTTL      TTL in seconds
	 */
	public function write(string $sessionId, array $sessionData, int $dataTTL);

	/**
	 * Reads and returns session data.
	 *
	 * @access  public
	 * @param   string  $sessionId  Session id
	 * @return  array
	 */
	public function read(string $sessionId): array;

	/**
	 * Destroys the session data assiciated with the provided id.
	 *
	 * @access  public
	 * @param   string   $sessionId  Session id
	 */
	public function delete(string $sessionId);

	/**
	 * Garbage collector that deletes expired session data.
	 *
	 * @access  public
	 * @param   int      $dataTTL  Data TTL in seconds
	 */
	public function gc(int $dataTTL);
}
