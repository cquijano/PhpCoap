<?php

namespace PhpCoap;

class PacketBuffer extends \Evenement\EventEmitter
{

	public $listening = false;
	private $packets = array();

	function __construct( $sock, \React\EventLoop\LoopInterface $loop )
	{
		$this->sock = $sock;
		$this->loop = $loop;
	}

	function send( $packet, $peer = null )
	{
		
        array_push( $this->packets, array( 'data' => $packet, 'peer' => $peer ) );

        if ( ! $this->listening )
        {
            $this->listening = true;

            $this->loop->addWriteStream($this->sock, array($this, 'handleSend'));
        }
	}

	private static function to_ipv6 ($address){
		$port=strrpos ( $address, ':' );
		$str = '['. substr($address, 0, $port) . ']' . substr($address, $port);
		return $str;
	}

	function handleSend()
	{
		$pkt = array_shift( $this->packets );

		if ( $pkt['peer'] !== null ){
			stream_socket_sendto( $this->sock, $pkt['data'], 0, self::to_ipv6($pkt['peer']) );
		}else
			stream_socket_sendto( $this->sock, $pkt['data'] );

		$this->packet = null;

		$this->emit( 'sent', array( $pkt ) );

		if ( count( $this->packets ) == 0 )
		{
			$this->listening = false;
			$this->loop->removeWriteStream( $this->sock );
			$this->emit( 'sent-all' );
		}
	}

	function close()
	{
		$this->packets = array();
		$this->loop->removeWriteStream( $this->sock );
	}

}