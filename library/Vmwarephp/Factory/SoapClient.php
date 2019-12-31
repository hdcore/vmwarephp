<?php
namespace Vmwarephp\Factory;
use \Vmwarephp\Exception as Ex;

class SoapClient {
	private $wsdlClassMapper;
	private $wsdlFilePath;

	function __construct(\Vmwarephp\WsdlClassMapper $mapper = null, $wsdlFilePath = null) {
		$this->wsdlClassMapper = $mapper ? : new \Vmwarephp\WsdlClassMapper;
		$this->wsdlFilePath = $wsdlFilePath ? : $this->getWsdlFilePath();
	}

	function make(\Vmwarephp\Vhost $vhost, $useExceptions = 1, $trace = 1) {
		$options = array(
			'trace' => $trace,
			'location' => $this->makeRequestsLocation($vhost),
			'exceptions' => $useExceptions,
			'connection_timeout' => 10,
			'classmap' => $this->wsdlClassMapper->getClassMap(),
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE
		);
		/* START ADD THESE LINES */
	        $options['stream_context'] = stream_context_create([
		    'ssl' => [
		        // set some SSL/TLS specific options
		        'verify_peer' => false,
		        'verify_peer_name' => false,
		        'allow_self_signed' => true
		    ]
	        ]);
	        if(!empty(getenv('HTTP_PROXY'))){
		    $options['proxy_host'] = parse_url(getenv('HTTP_PROXY'),PHP_URL_HOST);
		    $options['proxy_port'] = intval(parse_url(getenv('HTTP_PROXY'),PHP_URL_PORT)) ?: 80;
	        }
    		/* END ADD THESE LINES */
		$soapClient = $this->makeDefaultSoapClient($this->wsdlFilePath, $options);
		if (!$soapClient) throw new Ex\CannotCreateSoapClient();
		return $soapClient;
	}

	function getClientClassMap() {
		return $this->wsdlClassMapper->getClassMap();
	}

	protected function makeRequestsLocation(\Vmwarephp\Vhost $vhost) {
		return 'https://' . $vhost->host . '/sdk';
	}

	protected function makeDefaultSoapClient($wsdl, array $options) {
		return @new \Vmwarephp\SoapClient($wsdl, $options);
	}

	private function getWsdlFilePath() {
		return __DIR__ . '/../Wsdl/vimService.wsdl';
	}
}
