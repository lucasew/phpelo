{
  config,
  pkgs,
  lib,
  ...
}:

{
  options = {
    services.phpelo = {
      enable = (lib.mkEnableOption "phpelo") // {
        default = true;
      };
      php = lib.mkPackageOption pkgs "php" { };
      package = lib.mkOption {
        type = lib.types.package;
        default = pkgs.stdenv.mkDerivation {
          pname = "phpelo-scripts";
          version = "0.0.1";
          src = lib.cleanSource ./.;
          nativeBuildInputs = [ pkgs.findutils ];
          installPhase = ''
            mkdir -p $out
            find . -maxdepth 1 -name '*.php' -exec cp -t $out {} +
          '';
          dontBuild = true;
        };
        description = "Package containing the phpelo scripts";
      };
      scriptDir = lib.mkOption {
        description = "Where are the scripts";
        type = lib.types.path;
      };
      socket = lib.mkOption {
        description = "Where to listen socket for phpelo";
        default = "/run/phpelo.sock";
        type = lib.types.path;
      };
    };
  };

  config =
    let cfg = config.services.phpelo;
    in lib.mkIf cfg.enable {

      services.phpelo.scriptDir = lib.mkDefault cfg.package;

      systemd.sockets.phpelo = {
        restartTriggers = [ cfg.socket ];
        socketConfig = {
          ListenStream = cfg.socket;
          Accept = true;
        };
        partOf = [ "phpelo.service" ];
        wantedBy = [ "sockets.target" "multi-user.target" ];
      };

      systemd.slices.phpelo.sliceConfig = {
        MemoryMax = "64M";
        MemoryHigh = "16M";
        CPUQuota = "10%";
        ManagedOOMSwap = "kill";
        ManagedOOMPressure = "kill";
      };

      systemd.services."phpelo@" = {
        stopIfChanged = true;
        after = [ "network.target" ];
        serviceConfig = {
          Slice = "phpelo.slice";
          StandardInput = "socket";
          StandardOutput = "socket";
          StandardError = "journal";

          DevicePolicy = "closed";
          MemoryDenyWriteExecute = true;
          NoNewPrivileges = true;
          PrivateDevices = true;
          PrivateTmp = true;
          ProtectControlGroups = true;
          # ProtectHome = true;
          ProtectKernelModules = true;
          ProtectKernelTunables = true;
          ProtectKernelLogs = true;
          ProtectSystem = "strict";
        };

        script = ''
          export SCRIPT_DIR="${cfg.scriptDir}"
          exec ${lib.getExe cfg.php} -d display_errors="stderr" -d disable_functions="header" ${cfg.scriptDir}/entrypoint.php
        '';
      };
    };
}
