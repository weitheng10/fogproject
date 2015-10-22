<?php
class Printer extends FOGController {
    protected $databaseTable = 'printers';
    protected $databaseFields = array(
        'id' => 'pID',
        'name' => 'pAlias',
        'description' => 'pDesc',
        'port' => 'pPort',
        'file' => 'pDefFile',
        'model' => 'pModel',
        'config' => 'pConfig',
        'ip' => 'pIP',
        'pAnon2' => 'pAnon2',
        'pAnon3' => 'pAnon3',
        'pAnon4' => 'pAnon4',
        'pAnon5' => 'pAnon5',
    );
    protected $databaseFieldsRequired = array(
        'name',
    );
    protected $additionalFields = array(
        'hosts',
        'hostsnotinme',
    );
    public function destroy($field = 'id') {
        $this->getClass('PrinterAssociationManager')->destroy(array('printerID'=>$this->get('id')));
        return parent::destroy($field);
    }
    public function save() {
        parent::save();
        if ($this->isLoaded('hosts')) {
            $DBHostIDs = $this->getSubObjectIDs('PrinterAssociation',array('printerID'=>$this->get('id')),'hostID');
            $RemoveHostIDs = array_unique(array_diff((array)$DBHostIDs,(array)$this->get('hosts')));
            if (count($RemoveHostIDs)) {
                $this->getClass('PrinterAssociationManager')->destroy(array('hostID'=>$RemoveHostIDs,'printerID'=>$this->get('id')));
                $DBHostIDs = $this->getSubObjectIDs('PrinterAssociation',array('printerID'=>$this->get('id')),'hostID');
                unset($RemoveHostIDs);
            }
            $Hosts = array_diff((array)$this->get('hosts'),(array)$DBHostIDs);
            foreach ((array)$Hosts AS $i => &$Host) {
                $hasDefault = $this->getClass('PrinterAssociationManager')->count(array('isDefault'=>1,'hostID'=>$Host));
                $this->getClass('PrinterAssociation')
                    ->set('printerID',$this->get('id'))
                    ->set('hostID',$Host)
                    ->set('isDefault',($hasDefault != 1))
                    ->save();
            }
            unset($Host);
        }
        return $this;
    }
    public function addHost($addArray) {
        $this->set('hosts',array_unique(array_merge((array)$this->get('hosts'),(array)$addArray)));
        return $this;
    }
    public function removeHost($removeArray) {
        $this->set('hosts',array_unique(array_diff((array)$this->get('hosts'),(array)$removeArray)));
        return $this;
    }
    protected function loadHosts() {
        if ($this->get('id')) $this->set('hosts',$this->getSubObjectIDs('PrinterAssociation',array('printerID'=>$this->get('id')),'hostID'));
    }
    protected function loadHostsnotinme() {
        if ($this->get('id')) {
            $find = array('id'=>$this->get('hosts'));
            $this->set('hostsnotinme',$this->getSubObjectIDs('Host',$find,'id',true));
            unset($find);
        }
        return $this;
    }
    public function updateDefault($hostid,$onoff) {
        foreach ((array)$hostid AS $i => &$id) $this->getClass('Host',$id)->updateDefault($this->get('id'),in_array($id,(array)$onoff));
        unset($id);
        return $this;
    }
}
