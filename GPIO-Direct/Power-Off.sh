#!/bin/sh

#Using GPIOs 17 and 27 for Power-L and Power-R

#Set GPIO(s) for Output
/opt/fpp/src/fpp -G 17,Output
/opt/fpp/src/fpp -G 27,Output

#Turn GPIO(s) OFF
/opt/fpp/src/fpp -g 17,Output,0
/opt/fpp/src/fpp -g 27,Output,0
