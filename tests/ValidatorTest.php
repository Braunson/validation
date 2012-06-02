<?php

use Mockery as m;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

class ValidatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testInValidatableRulesReturnsValid()
	{
		$trans = $this->getTranslator();
		$trans->shouldReceive('trans')->never();
		$v = new Validator($trans, array('foo' => 'taylor'), array('name' => 'Confirmed'));
		$this->assertTrue($v->passes());
	}


	public function testProperLanguageLineIsSet()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => 'required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('required!', $v->errors->first('name'));
	}


	public function testAttributeNamesAreReplaced()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => ':attribute is required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('name is required!', $v->errors->first('name'));

		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => ':attribute is required!', 'validation.attributes.name' => 'Name'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('Name is required!', $v->errors->first('name'));
	}


	public function testCustomValidationLinesAreRespected()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.required' => 'required!', 'validation.name.required' => 'really required!'), 'en', 'messages');
		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());
		$this->assertEquals('really required!', $v->errors->first('name'));
	}


	public function testValidateRequired()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array(), array('name' => 'Required'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('name' => ''), array('name' => 'Required'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('name' => 'foo'), array('name' => 'Required'));
		$this->assertTrue($v->passes());

		$file = new File('', false);
		$v = new Validator($trans, array('name' => $file), array('name' => 'Required'));
		$this->assertFalse($v->passes());

		$file = new File(__FILE__, false);
		$v = new Validator($trans, array('name' => $file), array('name' => 'Required'));
		$this->assertTrue($v->passes());
	}


	public function testValidateConfirmed()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('password' => 'foo'), array('password' => 'Confirmed'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('password' => 'foo', 'password_confirmation' => 'bar'), array('password' => 'Confirmed'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('password' => 'foo', 'password_confirmation' => 'foo'), array('password' => 'Confirmed'));
		$this->assertTrue($v->passes());
	}


	public function testValidateSame()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'bar', 'baz' => 'boom'), array('foo' => 'Same:baz'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'bar'), array('foo' => 'Same:baz'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'bar', 'baz' => 'bar'), array('foo' => 'Same:baz'));
		$this->assertTrue($v->passes());
	}


	public function testValidateDifferent()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'bar', 'baz' => 'boom'), array('foo' => 'Different:baz'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => 'bar'), array('foo' => 'Different:baz'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'bar', 'baz' => 'bar'), array('foo' => 'Different:baz'));
		$this->assertFalse($v->passes());
	}


	public function testValidateAccepted()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'no'), array('foo' => 'Accepted'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => null), array('foo' => 'Accepted'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array(), array('foo' => 'Accepted'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'yes'), array('foo' => 'Accepted'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '1'), array('foo' => 'Accepted'));
		$this->assertTrue($v->passes());
	}


	public function testValidateNumeric()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'asdad'), array('foo' => 'Numeric'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '1.23'), array('foo' => 'Numeric'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '-1'), array('foo' => 'Numeric'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '1'), array('foo' => 'Numeric'));
		$this->assertTrue($v->passes());
	}


	public function testValidateInteger()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'asdad'), array('foo' => 'Integer'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '1.23'), array('foo' => 'Integer'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '-1'), array('foo' => 'Integer'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '1'), array('foo' => 'Integer'));
		$this->assertTrue($v->passes());
	}


	public function testValidateSize()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'asdad'), array('foo' => 'Size:3'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'anc'), array('foo' => 'Size:3'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '123'), array('foo' => 'Numeric|Size:3'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '3'), array('foo' => 'Numeric|Size:3'));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
		$v = new Validator($trans, array(), array('photo' => 'Size:3'));
		$v->setFiles(array('photo' => $file));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
		$v = new Validator($trans, array(), array('photo' => 'Size:3'));
		$v->setFiles(array('photo' => $file));
		$this->assertFalse($v->passes());		
	}


	public function testValidateBetween()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'asdad'), array('foo' => 'Between:3,4'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'anc'), array('foo' => 'Between:3,5'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => 'ancf'), array('foo' => 'Between:3,5'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => 'ancfs'), array('foo' => 'Between:3,5'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '123'), array('foo' => 'Numeric|Between:50,100'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '3'), array('foo' => 'Numeric|Between:1,5'));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
		$v = new Validator($trans, array(), array('photo' => 'Between:1,5'));
		$v->setFiles(array('photo' => $file));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
		$v = new Validator($trans, array(), array('photo' => 'Between:1,2'));
		$v->setFiles(array('photo' => $file));
		$this->assertFalse($v->passes());		
	}


	public function testValidateMin()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => '3'), array('foo' => 'Min:3'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'anc'), array('foo' => 'Min:3'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '2'), array('foo' => 'Numeric|Min:3'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '5'), array('foo' => 'Numeric|Min:3'));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
		$v = new Validator($trans, array(), array('photo' => 'Min:2'));
		$v->setFiles(array('photo' => $file));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
		$v = new Validator($trans, array(), array('photo' => 'Min:10'));
		$v->setFiles(array('photo' => $file));
		$this->assertFalse($v->passes());		
	}


	public function testValidateMax()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('foo' => 'aslksd'), array('foo' => 'Max:3'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => 'anc'), array('foo' => 'Max:3'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('foo' => '211'), array('foo' => 'Numeric|Max:100'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('foo' => '22'), array('foo' => 'Numeric|Max:33'));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(3072));
		$v = new Validator($trans, array(), array('photo' => 'Max:10'));
		$v->setFiles(array('photo' => $file));
		$this->assertTrue($v->passes());

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
		$v = new Validator($trans, array(), array('photo' => 'Max:2'));
		$v->setFiles(array('photo' => $file));
		$this->assertFalse($v->passes());		
	}


	public function testProperMessagesAreReturnedForSizes()
	{
		$trans = $this->getRealTranslator();
		$trans->addResource('array', array('validation.min.numeric' => 'numeric', 'validation.size.string' => 'string', 'validation.max.file' => 'file'), 'en', 'messages');
		$v = new Validator($trans, array('name' => '3'), array('name' => 'Numeric|Min:5'));
		$this->assertFalse($v->passes());
		$this->assertEquals('numeric', $v->errors->first('name'));

		$v = new Validator($trans, array('name' => 'asasdfadsfd'), array('name' => 'Size:2'));
		$this->assertFalse($v->passes());
		$this->assertEquals('string', $v->errors->first('name'));

		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('getSize'), array(__FILE__, false));
		$file->expects($this->any())->method('getSize')->will($this->returnValue(4072));
		$v = new Validator($trans, array(), array('photo' => 'Max:3'));
		$v->setFiles(array('photo' => $file));
		$this->assertFalse($v->passes());
		$this->assertEquals('file', $v->errors->first('photo'));
	}


	public function testValidateIn()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('name' => 'foo'), array('name' => 'In:bar,baz'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('name' => 'foo'), array('name' => 'In:foo,baz'));
		$this->assertTrue($v->passes());
	}


	public function testValidateNotIn()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('name' => 'foo'), array('name' => 'NotIn:bar,baz'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('name' => 'foo'), array('name' => 'NotIn:foo,baz'));
		$this->assertFalse($v->passes());
	}


	public function testValidateUnique()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('email' => 'foo'), array('email' => 'Unique:users'));
		$mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo', null, null)->andReturn(0);
		$v->setPresenceVerifier($mock);
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('email' => 'foo'), array('email' => 'Unique:users,email_addr,1'));
		$mock2 = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock2->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id')->andReturn(1);
		$v->setPresenceVerifier($mock2);
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('email' => 'foo'), array('email' => 'Unique:users,email_addr,1,id_col'));
		$mock3 = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock3->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo', '1', 'id_col')->andReturn(2);
		$v->setPresenceVerifier($mock3);
		$this->assertFalse($v->passes());
	}


	public function testValidationExists()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('email' => 'foo'), array('email' => 'Exists:users'));
		$mock = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock->shouldReceive('getCount')->once()->with('users', 'email', 'foo')->andReturn(true);
		$v->setPresenceVerifier($mock);
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('email' => 'foo'), array('email' => 'Exists:users,email_addr'));
		$mock2 = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock2->shouldReceive('getCount')->once()->with('users', 'email_addr', 'foo')->andReturn(false);
		$v->setPresenceVerifier($mock2);
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('email' => array('foo')), array('email' => 'Exists:users,email_addr'));
		$mock3 = m::mock('Illuminate\Validation\PresenceVerifierInterface');
		$mock3->shouldReceive('getMultiCount')->once()->with('users', 'email_addr', array('foo'))->andReturn(false);
		$v->setPresenceVerifier($mock3);
		$this->assertFalse($v->passes());
	}


	public function testValidateIp()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('ip' => 'aslsdlks'), array('ip' => 'Ip'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('ip' => '127.0.0.1'), array('ip' => 'Ip'));
		$this->assertTrue($v->passes());
	}


	public function testValidateEmail()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'aslsdlks'), array('x' => 'Email'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('x' => 'foo@gmail.com'), array('x' => 'Email'));
		$this->assertTrue($v->passes());
	}


	public function testValidateUrl()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'aslsdlks'), array('x' => 'Url'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('x' => 'http://google.com'), array('x' => 'Url'));
		$this->assertTrue($v->passes());
	}


	public function testValidateActiveUrl()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'aslsdlks'), array('x' => 'ActiveUrl'));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array('x' => 'http://google.com'), array('x' => 'ActiveUrl'));
		$this->assertTrue($v->passes());
	}


	/**
	 * Also covers the "Mimes" validation rule.
	 */
	public function testValidateImage()
	{
		$trans = $this->getRealTranslator();
		$file = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('guessExtension'), array(__FILE__, false));
		$file->expects($this->any())->method('guessExtension')->will($this->returnValue('php'));
		$v = new Validator($trans, array(), array('x' => 'Image'));
		$v->setFiles(array('x' => $file));
		$this->assertFalse($v->passes());

		$v = new Validator($trans, array(), array('x' => 'Image'));
		$file2 = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('guessExtension'), array(__FILE__, false));
		$file2->expects($this->any())->method('guessExtension')->will($this->returnValue('jpeg'));
		$v->setFiles(array('x' => $file2));
		$this->assertTrue($v->passes());

		$file3 = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('guessExtension'), array(__FILE__, false));
		$file3->expects($this->any())->method('guessExtension')->will($this->returnValue('gif'));
		$v->setFiles(array('x' => $file3));
		$this->assertTrue($v->passes());

		$file4 = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('guessExtension'), array(__FILE__, false));
		$file4->expects($this->any())->method('guessExtension')->will($this->returnValue('bmp'));
		$v->setFiles(array('x' => $file4));
		$this->assertTrue($v->passes());

		$file5 = $this->getMock('Symfony\Component\HttpFoundation\File\File', array('guessExtension'), array(__FILE__, false));
		$file5->expects($this->any())->method('guessExtension')->will($this->returnValue('png'));
		$v->setFiles(array('x' => $file5));
		$this->assertTrue($v->passes());
	}


	public function testValidateAlpha()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'aslsdlks'), array('x' => 'Alpha'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('x' => 'http://google.com'), array('x' => 'Alpha'));
		$this->assertFalse($v->passes());
	}


	public function testValidateAlphaNum()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'asls13dlks'), array('x' => 'AlphaNum'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('x' => 'http://g232oogle.com'), array('x' => 'AlphaNum'));
		$this->assertFalse($v->passes());
	}


	public function testValidateAlphaDash()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'asls1-_3dlks'), array('x' => 'AlphaDash'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('x' => 'http://-g232oogle.com'), array('x' => 'AlphaDash'));
		$this->assertFalse($v->passes());
	}


	public function testValidateRegex()
	{
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => 'asdasdf'), array('x' => 'Regex:/^([a-z])+$/i'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('x' => 'aasd234fsd1'), array('x' => 'Regex:/^([a-z])+$/i'));
		$this->assertFalse($v->passes());	
	}


	public function testBeforeAndAfter()
	{
		date_default_timezone_set('UTC');
		$trans = $this->getRealTranslator();
		$v = new Validator($trans, array('x' => '2000-01-01'), array('x' => 'Before:2012-01-01'));
		$this->assertTrue($v->passes());

		$v = new Validator($trans, array('x' => '2012-01-01'), array('x' => 'After:2000-01-01'));
		$this->assertTrue($v->passes());
	}


	protected function getTranslator()
	{
		return m::mock('Symfony\Component\Translation\TranslatorInterface');
	}


	protected function getRealTranslator()
	{
		$trans = new Symfony\Component\Translation\Translator('en');
		$trans->addLoader('array', new Symfony\Component\Translation\Loader\ArrayLoader);
		return $trans;
	}

}