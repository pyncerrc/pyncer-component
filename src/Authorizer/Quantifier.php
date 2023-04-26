<?php
namespace Pyncer\Component\Authorizer;

enum Quantifier
{
    case ANY;
    case ALL;
    case ONE;
    case NONE;
}
