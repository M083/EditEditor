<?php

namespace EE;

# Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

# Other
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;

# Event
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;

class core extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function PlayerInteractEvent(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$level = $player->getLevel();
		$item = $event->getItem();
		$block = $event->getBlock();

		if($player->getGamemode() == 1 && $item->getId() == 352 && ($block->x != 0 || $block->y != 0 || $block->z != 0)){
			
			if(!isset($player->EE)){
				$player->EE = new EE($block->x, $block->y, $block->z, Block::get(0, 0), $player);
			}

			switch($player->EE->degree){

				case 0:
					$player->EE->degree = 1;
					$player->EE->setPos($block->x, $block->y, $block->z);
					$player->sendMessage("§b[EE] §2始点を設定したよ！！ もう一度どこかをタッチすると作成を開始するよ！！");

				break;

				case 1:
					$player->EE->degree = 2;
					$player->sendMessage("§b[EE] §2作成中・・・");
					$player->EE->create();
					$player->sendMessage("§b[EE] §2作成完了！！　ブロックを壊すとエディターを使用できるよ！！\n§2再設定する場合はスニークしてブロックをタッチしてね！！\n§モード変更する場合はスニークしてブロックを壊してね！！");					
				break;
			}

			if($player->EE->degree == 2 && $player->isSneaking()){
				$player->sendMessage("§b[EE] §2設定を初期化したよ！！");
				unset($player->EE);
			}
		}
	}

	public function BlockBreakEvent(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$level = $player->getLevel();
		$item = $event->getItem();
		$block = $event->getBlock();

		if($player->getGamemode() == 1 && $item->getId() == 352){
			
			if(isset($player->EE)){

				if($player->isSneaking()){
					$player->EE->modeChange($block);
					$event->setCancelled();
				}else{
					$player->EE->edit($block->x, $block->y, $block->z);
					$event->setCancelled();
				}
			}
			else{
				$player->sendMessage("§b[EE] §2あれれ？ まだエディターを作成してないよ？　".$player->getName()."はせっかちだなぁ・・・\n§2あ、もしかして使い方わからない？　しょうがないなぁ...使い方を解説していくね！！\n§2まずはエディターの中心とするブロックを骨でタッチしてね！！");
			}
		}
	}
}

class EE extends core{
	
	public function __construct($x, $y, $z, $block, $player){
		$this->player = $player;
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->con = [];
		$this->count = 0;
		$this->degree = 0;
		$this->block = $block;
		$this->mode = 0;
	}

	public function modeChange($block){

		if($this->mode == 0){
			$this->mode = 1;
			$this->player->sendMessage("§b[EE] §2設置モードに変更したよ！！");
			$this->block = $block;
		}
		else{
			$this->mode = 1;
			$this->player->sendMessage("§b[EE] §2削除モードに変更したよ！！");
			$this->block = Block::get(0, 0);
		}
	}

	public function setPos($x, $y, $z){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	public function create(){
		$this->search($this->x, $this->y, $this->z, 0, 0, 0, Block::get(0, 0));

		if($this->count >= 100){
			$this->con = [];
			$this->player->sendMessage("§b[EE] §4ブロックが多すぎるよ！！！　危ないから強制終了させたよ！！　ブロックは100個以内にしてね！！");
		}
	}

	public function search($x, $y, $z, $vx, $vy, $vz, $air){
		$pos = new Vector3($x+$vx, $y+$vy, $z+$vz);
		$level = $this->player->getLevel();

		if($level->getBlockIdAt($x+$vx, $y+$vy, $z+$vz) != 0 && $this->count <= 100){
			$level->setBlock($pos, $air);
			array_push($this->con, [$vx, $vy, $vz]);
			$this->count += 1;
			$this->search($x, $y, $z, $vx+1, $vy, $vz, $air);
			$this->search($x, $y, $z, $vx-1, $vy, $vz, $air);
			$this->search($x, $y, $z, $vx, $vy+1, $vz, $air);
			$this->search($x, $y, $z, $vx, $vy-1, $vz, $air);
			$this->search($x, $y, $z, $vx, $vy, $vz+1, $air);
			$this->search($x, $y, $z, $vx, $vy, $vz-1, $air);
		}
	}

	public function edit($x, $y, $z){
		$level = $this->player->getLevel();

		foreach($this->con as $i => $value){
			list($vx, $vy, $vz) = $this->con[$i];
			$level->setBlock(new Vector3($x+$vx, $y+$vy, $z+$vz), $this->block);
		}
	}
}