<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;

use App\Models\Cargo;

use \App\Models\Rescisao;

use Illuminate\Http\Request;

class FuncionarioController extends Controller
{
    public function funcionarios() {
        $funcionarios = Funcionario::all();
        
        return view('funcionarios', [
            'funcionarios' => $funcionarios
        ]);
    }
    
    public function cadastrarFuncionariosForm() {
        return view('cadastro/funcionario', ['cargos' => Cargo::all()]);
    }
    
    public function cadastrarFuncionario() {
        $cargo = \Request::input('cargo');
        
        $funcionario = new Funcionario();
        $funcionario->nome = \Request::input('nome');
        $funcionario->cpf = \Request::input('cpf');
        $funcionario->ativo = 1;
        $funcionario->salario = \Request::input('salario');
        $funcionario->dataAdmissao = \Request::input('date');
        $funcionario->cargo_id = Cargo::where('nome', $cargo)->first()->id;
        
        $funcionario->save();
        
        return redirect('funcionarios');
    }
    
    public function alterarFuncionarioForm($id) {
        return view('altera.funcionario', ['funcionario' => Funcionario::find($id), 'carg' => Cargo::find(Funcionario::find($id)->cargo_id), 'cargos' => Cargo::all()]);
    }
    
    public function alterarFuncionario($id) {
        $cargo = \Request::input('cargo');
        
        $funcionario = Funcionario::find($id);
        
        $funcionario->nome = \Request::input('nome');
        $funcionario->cpf = \Request::input('cpf');
        $funcionario->salario = \Request::input('salario');
        $funcionario->cargo_id = Cargo::where('nome', $cargo)->first()->id;
        
        $funcionario->save();
        
        return redirect('funcionarios');
    }
    
    public function excluiFuncionario($id) {
        $funcionario = Funcionario::find($id);       
        $funcionario->delete();
        
        return redirect('funcionarios');
    }
    
    public function rescisaoFuncionario($id) {
        $rescisao = Rescisao::where('funcionario_id', $id)->first();
        return view('rescisao', ['rescisao' => $rescisao, 'id' => $id]);
    }
    
    public function calcularRescisao($id) {
        $dataAd = \Request::input('dataA');
        $dataSai = \Request::input('dataS');
        $multa = \Request::input('multa');
        $salario = Funcionario::find($id)->salario;

        $decimoTerceiro = $this->decimoTerceiro($dataAd, $dataSai, $salario);
        $feriasProporcionais = $this->feriasProporcionais($dataAd, $dataSai, $salario);
        $multa = $this->multa($dataAd, $dataSai, $multa, $salario);

        return view('rescisao.calculo', [
            'id' => $id,
            'dataSaida' => $dataSai,
            'decimoTerceiro' => $decimoTerceiro,
            'ferias' => $feriasProporcionais,
            'multa' => $multa,
        ]);
    }

    public function decimoTerceiro($dataA, $dataS, $salario) {
        $dataAd = explode("-", $dataA);
        $dataSai = explode("-", $dataS);

        if ($dataAd[0] == $dataSai[0]){
            $qtdMeses = $dataSai[1] - $dataAd[1];
            $decimoTerceiro = ($salario*$qtdMeses)/12;
            return $decimoTerceiro;
        } else if ($dataAd[0] > $dataSai[0]) {
            return null;
        } else {
            $qtdMeses = $dataSai[1];
            $decimoTerceiro = ($salario*($qtdMeses-1))/12;
            return $decimoTerceiro;
        }   
    }

    public function feriasProporcionais($dataA, $dataS, $salario) {
        $feriasCompleta = $salario + ($salario/3);

        $dataAd = explode("-", $dataA);
        $dataSai = explode("-", $dataS);

        if($dataSai[0]-$dataAd[0] == 0) {
            $qtdMeses = $dataSai[1] - $dataAd[1];
            $ferias = ($qtdMeses/12)*$feriasCompleta;
            return $ferias;
        } else if ($dataAd[0] > $dataSai[0]) {
            return null; 
        } else {
            $ferias = $feriasCompleta + (($dataSai[1]-1)/12)*$feriasCompleta;
            return floor($ferias);
        }
    }

    public function multa($dataA, $dataS, $multa, $salario) {  
        $diferenca = strtotime($dataS) - strtotime($dataA);
        $qtdMeses = floor($diferenca / (60 * 60 * 24 * 30));

        $multaFinal = $salario*($multa/100)*$qtdMeses;
        return $multaFinal;
    }

    public function demitirFuncionario($id) {
        $funcionario = Funcionario::find($id);
        $funcionario->ativo = 0;
        $funcionario->save();
        //$this->cadastraRescisao($id, $dataS, $multa, $decTer, $ferias);
        return redirect('funcionarios');
    }
    /*
    public  function cadastraRescisao($id, $dataS, $multa, $decTer, $ferias) {
        $rescisao = new Rescisao();

        $rescisao->data = $dataS;
        $rescisao->multa = $multa;
        $rescisao->efetuada = 1;
        $rescisao->decTer = $decTer;
        $rescisao->ferias = $ferias;

        $rescisao->save();
        return redirect('funcionarios');
    }
    */
}
