{ pkgs ? import <nixpkgs> { } }:

(import ./test.nix { inherit pkgs; })
