<?php

class StatssalesProbe extends statssales
{
    public function probeGetData($layers)
    {
        if (!$this->_employee) {
            $this->setEmployee(Context::getContext()->employee->id);
        }
        if (!$this->_id_lang) {
            $this->setLang(Context::getContext()->language->id);
        }
        $this->getData($layers);
    }

    public function probeValues()
    {
        return $this->_values;
    }

    public function probeLegend()
    {
        return $this->_legend;
    }

    public function probeTitles()
    {
        return $this->_titles;
    }

    public function probeSetLang($id_lang)
    {
        $this->setLang($id_lang);
    }
}
