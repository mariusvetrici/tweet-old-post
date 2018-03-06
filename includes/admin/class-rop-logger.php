<?php
/**
 * The file that defines the Log specific methods.
 *
 * A class that is used to log events.
 * It uses Monolog.
 *
 * @link       https://themeisle.com/
 * @since      8.0.0
 *
 * @package    Rop
 * @subpackage Rop/includes/admin
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Class Rop_Logger
 *
 * @since   8.0.0
 * @link    https://themeisle.com/
 */
class Rop_Logger {

	/**
	 * The log date format.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     string $date_format The date format string.
	 */
	private $date_format;

	/**
	 * The format for each log line.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     string $output_format A string specifying the log line format.
	 */
	private $output_format;

	/**
	 * Path to the log file.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     string $file The path to the log file.
	 */
	private $file;

	/**
	 * Path to the log file.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     string $file The path to the log file.
	 */
	private $file_verbose;

	/**
	 * An instance of the Logger class.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     Logger $logger An instance of the Logger class.
	 */
	private $logger;

	/**
	 * An instance of the Logger class.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     Logger $logger_verbose An instance of the Logger class.
	 */
	private $logger_verbose;

	/**
	 * Rop_Logger constructor.
	 * Instantiate the Logger with specified formatter and stream.
	 *
	 * @since   8.0.0
	 * @access  public
	 */
	public function __construct() {

		$this->date_format   = 'd-m-Y H:i:s';
		$this->output_format = '%datetime% > %level_name% > %message% %context% %extra%' . PHP_EOL;
		$this->file          = ROP_PATH . '/logs/rop.log';
		$this->file_verbose  = ROP_PATH . '/logs/rop_verbose.log';

		$formatter = new LineFormatter( $this->output_format, $this->date_format );

		$stream_pretty  = new StreamHandler( $this->file, Logger::DEBUG );
		$stream_verbose = new StreamHandler( $this->file_verbose, Logger::DEBUG );

		$stream_pretty->setFormatter( $formatter );
		$stream_verbose->setFormatter( $formatter );

		$this->logger = new Logger( 'rop_logs' );
		$this->logger->pushHandler( $stream_pretty );

		$this->logger_verbose = new Logger( 'rop_logs_verbose' );
		$this->logger_verbose->pushHandler( $stream_verbose );
	}

	/**
	 * Logs an info message.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   string $message The message to log.
	 * @param   array  $context [optional] A context for the message, if needed.
	 */
	public function info( $message = '', $context = array() ) {
		$this->logger->info( $message );
		$this->logger_verbose->info( $message, $context );
	}

	/**
	 * Logs a warning message.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   string $message The message to log.
	 * @param   array  $context [optional] A context for the message, if needed.
	 */
	public function warn( $message = '', $context = array() ) {
		$this->logger->warn( $message );
		$this->logger_verbose->warn( $message, $context );
	}

	/**
	 * Logs an error message.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   string $message The message to log.
	 * @param   array  $context [optional] A context for the message, if needed.
	 */
	public function error( $message = '', $context = array() ) {
		$this->logger->error( $message );
		$this->logger_verbose->error( $message, $context );
	}

	/**
	 * Method to read the log file.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @return string
	 */
	public function read( $show_verbose = false ) {
		$logs = 'There are no logs yet!';

		$file = $this->file;
		if ( $show_verbose ) {
			$file = $this->file_verbose;
		}

		if ( file_exists( $file ) ) {
			$logs = file_get_contents( $file );
		}
		return $logs;
	}
}
