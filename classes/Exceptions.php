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
class HandlerLogicIncorrectLanguageCodeException extends \Exception {}
class HandlerLogicIncorrectXmlStringException extends \Exception {}
class HandlerLogicIncorrectObjectIdException extends \Exception {}
class HandlerLogicIncorrectDateFormatException extends \Exception {}
class HandlerLogicIncorrectTimestampException extends \Exception {}
class HandlerLogicIncorrectLovIdentifierException extends \Exception {}
class HandlerLogicIncorrectLovTypeException extends \Exception {}