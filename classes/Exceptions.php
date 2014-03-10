<?php
namespace MakingWaves\eZLumesse;

/**
 * Exception classes for Soap class
 */
class SoapMissingEnvironmentException extends \Exception {}
class SoapIncorrectFunctionNameException extends \Exception {}

/**
 * Exception classes for HandlerLogic class
 */
class HandlerLogicConnectionFailureException extends \Exception {}
class HandlerLogicIncorrectRangeException extends \Exception {}
class HandlerLogicIncorrectIntegersException extends \Exception {}
class HandlerLogicTotalResultsMissingException extends \Exception {}