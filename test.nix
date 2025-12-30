{ pkgs ? import <nixpkgs> { } }:

let
  phpelo-module = import ./nixos.nix;
  example-routes = pkgs.stdenv.mkDerivation {
    pname = "phpelo-example-routes";
    version = "0.0.1";
    src = pkgs.lib.cleanSource ./examples;
    installPhase = ''
      mkdir -p $out
      cp -r ./* $out
    '';
    dontBuild = true;
  };
in

pkgs.testers.runNixOSTest {
  name = "phpelo-test";
  nodes.machine = { pkgs, ... }: {
    imports = [ phpelo-module ];
    services.phpelo = {
      enable = true;
      scriptDir = example-routes;
    };
  };

  testScript = { nodes, ... }: ''
    start_all()
    machine.wait_for_unit("phpelo.socket")
    output = machine.succeed(
        "${pkgs.socat}/bin/socat - UNIX-CONNECT:/run/phpelo.sock <<< $'GET /ok HTTP/1.0\\r\\n\\r\\n'"
    )
    assert "200" in output
    assert "ok" in output
  '';
}
