<?php 

interface IObservable{
	public function attachObserver(IObserver $obj);
	public function notfyObservers($messageArray);
	public function getSets($partition);
}

interface IObserver{
	public function update($messageArray, $sets);
	public function getName();
}